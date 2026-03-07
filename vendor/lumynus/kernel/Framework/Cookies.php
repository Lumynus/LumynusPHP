<?php

declare(strict_types=1);

/**
 * @author WelenySantos de Oliveira <welenysantos@gmail.com>
 * @package Lumynus\Framework
 */

namespace Lumynus\Framework;

use Lumynus\Framework\Config;

final class Cookies extends LumaClasses implements \Lumynus\Contracts\CookieInterface
{

    private array $cookieParams = [];
    private string $secretKey;
    private const PREFIX = 'LUM_';

    /**
     * Constructor to initialize default cookie settings and secret key automatically.
     */
    public function __construct()
    {
        if (!in_array('aes-256-gcm', openssl_get_cipher_methods(), true)) {
            throw new \RuntimeException('AES-256-GCM not supported on this server.');
        }

        $secret = Config::getApplicationConfig()['security']['cookie']['secret'] ?? 'LumynusApp';
        $this->secretKey = $this->generateSecretKey($secret);

        $this->cookieParams = [
            'path' => '/',
            'domain' => Config::getApplicationConfig()['App']['domain'] ?? '',
            'secure' => Config::modeProduction(),
            'httponly' => Config::modeProduction(),
            'samesite' => 'Strict'
        ];
        $this->applyExistingCookies();
    }

    /**
     * Gera a chave secreta baseada no nome da aplicação.
     */
    private function generateSecretKey(string $secret): string
    {
        return  hash_hkdf(
            'sha256',
            $secret,
            32,
            'LumynusCookieEncryption',
            'LumynusSalt'
        );
    }

    /**
     * Retorna o nome do cookie com o prefixo do framework.
     */
    private function getRealKey(string $key): string
    {
        if (str_starts_with($key, self::PREFIX)) {
            return $key;
        }
        return self::PREFIX . $key;
    }


    /**
     * Aplica cookies existentes, validando assinatura e descriptografando.
     */
    private function applyExistingCookies(): void
    {
        if (!isset($_SERVER['REQUEST_TIME_FLOAT'])) {
            return;
        }

        foreach (array_keys($_COOKIE) as $key) {
            if (!str_starts_with($key, self::PREFIX)) {
                continue;
            }

            $cleanKey = substr($key, strlen(self::PREFIX));
            if ($this->get($cleanKey) === null) {
                $this->remove($cleanKey);
                Logs::register(
                    'Cookies: Invalid or corrupted cookie removed.',
                    ['key' => $key]
                );
            }
        }
    }

    /**
     * Define um cookie com criptografia e assinatura.
     * @param string $key Nome do cookie.
     * @param mixed $value Valor do cookie.
     * @param int $expire Tempo de expiração em segundos (0 para sessão).
     * @param array $options Opções adicionais para o cookie (path, domain, secure, httponly, samesite).
     * @return void
     */
    public function set(
        string $key,
        mixed $value,
        int $expire = 0,
        array $options = []
    ): void {
        $realKey = $this->getRealKey($key);
        $expireTime = $expire > 0 ? time() + $expire : 0;
        $params = array_merge($this->cookieParams, $options);

        try {
            $payload = json_encode($value, JSON_THROW_ON_ERROR);
            $iv = random_bytes(12);
            $aad = $realKey;

            $ciphertext = openssl_encrypt(
                $payload,
                'aes-256-gcm',
                $this->secretKey,
                OPENSSL_RAW_DATA,
                $iv,
                $tag,
                $aad
            );

            if ($ciphertext === false) {
                throw new \RuntimeException('Failed to encrypt cookie.');
            }

            $cookieValue = base64_encode($iv . $tag . $ciphertext);

            $max = 5096 - strlen($realKey) - 200;

            if (strlen($cookieValue) > $max) {
                throw new \RuntimeException(
                    sprintf(
                        'Cookie "%s" size exceeded (%d bytes).',
                        $realKey,
                        strlen($cookieValue)
                    )
                );
            }

            $cookieOptions = $params;
            $cookieOptions['expires'] = $expireTime;

            if (headers_sent()) {
                Logs::register("Cookies: Failed to set '{key}'. Headers already sent.", ['key' => $realKey]);
                return;
            }

            setcookie($realKey, $cookieValue, $cookieOptions);

            $_COOKIE[$realKey] = $cookieValue;
        } catch (\Throwable $e) {
            Logs::register("Cookies: Error processing set({key}): {msg}", ['key' => $realKey, 'msg' => $e->getMessage()]);
        }
    }

    /**
     * Obtém e valida um cookie, retornando seu valor descriptografado.
     * @param string $key Nome do cookie.
     * @return mixed Valor do cookie ou null se não existir ou for inválido.
     */
    public function get(string $key): mixed
    {
        $realKey = $this->getRealKey($key);
        if (!isset($_COOKIE[$realKey])) {
            return null;
        }

        $decoded = base64_decode($_COOKIE[$realKey], true);
        if ($decoded === false || strlen($decoded) < 28) {
            return null;
        }

        $iv         = substr($decoded, 0, 12);
        $tag        = substr($decoded, 12, 16);
        $ciphertext = substr($decoded, 28);

        $aad = $realKey;

        $plain = openssl_decrypt(
            $ciphertext,
            'aes-256-gcm',
            $this->secretKey,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            $aad
        );

        if ($plain === false) {
            Logs::register("Cookies: Integrity failure in cookie '{key}'. Possible manipulation.", ['key' => $realKey]);
            return null;
        }
        try {
            return json_decode($plain, true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable $th) {
            return null;
        }
    }

    /**
     * Verifica se um cookie existe e é válido.
     * @param string $key Nome do cookie.
     * @return bool Verdadeiro se o cookie existir e for válido, falso caso contrário.
     */
    public function has(string $key): bool
    {
        $realKey = $this->getRealKey($key);
        return isset($_COOKIE[$realKey]) && $this->get($key) !== null;
    }

    /**
     * Remove um cookie.
     * @param string $key Nome do cookie.
     * @param array $options Opções adicionais para o cookie (path, domain, secure, httponly, samesite).
     * @return void
     */
    public function remove(string $key, array $options = []): void
    {
        $realKey = $this->getRealKey($key);

        if (headers_sent()) {
            unset($_COOKIE[$realKey]);
            return;
        }

        $params = array_merge($this->cookieParams, $options);

        setcookie($realKey, '', [
            'expires'  => time() - 3600,
            'path'     => $params['path'],
            'domain'   => $params['domain'],
            'secure'   => $params['secure'],
            'httponly' => $params['httponly'],
            'samesite' => $params['samesite'],
        ]);

        unset($_COOKIE[$realKey]);
    }

    /**
     * Limpa todos os cookies.
     * @return void
     */
    public function clear(): void
    {
        if (headers_sent()) {
            $_COOKIE = [];
            return;
        }
        foreach (array_keys($_COOKIE) as $key) {
            if (str_starts_with($key, self::PREFIX)) {
                $cleanKey = substr($key, strlen(self::PREFIX));
                $this->remove($cleanKey);
            }
        }
    }

    /**
     * Regenera todos os cookies mantendo seus valores.
     * @return void
     */
    public function regenerate(): void
    {
        if (headers_sent()) {
            return;
        }
        foreach ($_COOKIE as $key => $value) {
            if (!str_starts_with($key, self::PREFIX)) {
                continue;
            }

            $cleanKey = substr($key, strlen(self::PREFIX));
            $val = $this->get($cleanKey);

            if ($val !== null) {
                $this->set($cleanKey, $val);
            }
        }
    }

    /**
     * Gera um ID único para o conjunto atual de cookies.
     * @return string ID único baseado no conteúdo dos cookies.
     */
    public function getId(): string
    {
        return hash('sha256', json_encode($this->getAll()));
    }

    /**
     * Obtém todos os cookies válidos como um array associativo.
     * @return array Array associativo de todos os cookies válidos.
     */
    public function getAll(): array
    {
        $all = [];
        foreach ($_COOKIE as $key => $value) {
            if (!str_starts_with($key, self::PREFIX)) {
                continue;
            }

            $cleanKey = substr($key, strlen(self::PREFIX));
            $val = $this->get($cleanKey);
            if ($val !== null) {
                $all[$cleanKey] = $val;
            }
        }
        return $all;
    }


    public function __debugInfo(): array
    {
        return [
            'Lumynus' => "Framework PHP"
        ];
    }
}
