<?php

declare(strict_types=1);

namespace Lumynus\Bundle\Framework;

use Lumynus\Bundle\Framework\Config;

final class Cookies extends LumaClasses implements \Lumynus\Bundle\Contracts\SessionInterface
{
    private bool $autostart = true;
    private array $cookieParams = [];
    private string $secretKey;

    /**
     * Constructor to initialize default cookie settings and secret key automatically.
     */
    public function __construct(bool $autostart = true)
    {
        $this->autostart = $autostart;
        $secret = Config::getAplicationConfig()['security']['cookie']['secret'] ?? 'LumynusApp';
        $this->secretKey = $this->generateSecretKey($secret);

        $this->cookieParams = [
            'path' => Config::getAplicationConfig()['App']['host'] ?? '/',
            'domain' => '',
            'secure' => Config::modeProduction(),
            'httponly' => Config::modeProduction(),
            'samesite' => 'Strict'
        ];

        if ($this->autostart) {
            $this->applyExistingCookies();
        }
    }

    /**
     * Gera a chave secreta baseada no nome da aplicação.
     */
    private function generateSecretKey(string $secret): string
    {
        $salt = 'LumynusInternalSalt_2025';
        return hash('sha256', $secret . $salt);
    }

    /**
     * Aplica cookies existentes, validando assinatura e descriptografando.
     */
    private function applyExistingCookies(): void
    {
        foreach ($_COOKIE as $key => $value) {
            $val = $this->get($key);
            if ($val === null) {
                unset($_COOKIE[$key]); // Cookie inválido ou adulterado
            }
        }
    }

    public function set(string $key, mixed $value, int $expire = 0): void
    {
        $expireTime = $expire > 0 ? time() + $expire : 0;
        $serialized = serialize($value);

        $iv = random_bytes(16);
        $encrypted = openssl_encrypt($serialized, 'AES-256-CBC', $this->secretKey, 0, $iv);
        $data = base64_encode($iv . $encrypted);
        $hash = hash_hmac('sha256', $data, $this->secretKey);
        $cookieValue = $data . '|' . $hash;

        setcookie(
            $key,
            $cookieValue,
            [
                'expires' => $expireTime,
                'path' => $this->cookieParams['path'],
                'domain' => $this->cookieParams['domain'],
                'secure' => $this->cookieParams['secure'],
                'httponly' => $this->cookieParams['httponly'],
                'samesite' => $this->cookieParams['samesite'],
            ]
        );

        $_COOKIE[$key] = $cookieValue;
    }

    public function get(string $key): mixed
    {
        if (!isset($_COOKIE[$key])) return null;

        $parts = explode('|', $_COOKIE[$key]);
        if (count($parts) !== 2) return null;

        [$data, $hash] = $parts;
        if ($hash !== hash_hmac('sha256', $data, $this->secretKey)) {
            unset($_COOKIE[$key]);
            return null;
        }

        $decoded = base64_decode($data);
        $iv = substr($decoded, 0, 16);
        $encrypted = substr($decoded, 16);
        $decrypted = openssl_decrypt($encrypted, 'AES-256-CBC', $this->secretKey, 0, $iv);

        return $decrypted !== false ? unserialize($decrypted) : null;
    }

    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }

    public function remove(string $key): void
    {
        setcookie(
            $key,
            '',
            [
                'expires' => time() - 3600,
                'path' => $this->cookieParams['path'],
                'domain' => $this->cookieParams['domain'],
                'secure' => $this->cookieParams['secure'],
                'httponly' => $this->cookieParams['httponly'],
                'samesite' => $this->cookieParams['samesite'],
            ]
        );
        unset($_COOKIE[$key]);
    }

    public function clear(): void
    {
        foreach ($_COOKIE as $key => $value) {
            $this->remove($key);
        }
    }

    public function regenerate(): void
    {
        foreach ($_COOKIE as $key => $value) {
            $val = $this->get($key);
            if ($val !== null) $this->set($key, $val);
        }
    }

    public function getId(): string
    {
        return md5(json_encode($this->getAll()));
    }

    public function getAll(): array
    {
        $all = [];
        foreach ($_COOKIE as $key => $value) {
            $val = $this->get($key);
            if ($val !== null) $all[$key] = $val;
        }
        return $all;
    }

    public function createConfig(callable $callback): void
    {
        if ($this->autostart) return;
        $callback($this->cookieParams);
    }

    public function __debugInfo(): array
    {
        return [
            'Lumynus' => "Framework PHP - Cookies Criptografados e Assinados (Chave gerenciada automaticamente)"
        ];
    }
}
