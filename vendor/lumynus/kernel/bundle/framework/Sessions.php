<?php

declare(strict_types=1);

namespace Lumynus\Bundle\Framework;

use Lumynus\Bundle\Framework\LumaClasses;
use Lumynus\Bundle\Framework\Config;

final class Sessions extends LumaClasses implements \Lumynus\Bundle\Contracts\SessionInterface
{

    private bool $autostart = true;
    private string $secret;

    /**
     * Constructor to initialize the session.
     */
    public function __construct(bool $autostart = true)
    {
        $this->autostart = $autostart;
        $this->secret = Config::getAplicationConfig()['security']['session']['secret'] ?? 'LumynusApp';

        if (!$autostart) {
            return;
        }

        if (session_status() === PHP_SESSION_NONE) {

            session_name('LumynusSession_' . Config::getAplicationConfig()['App']['nameApplication']);

            session_start([
                'use_trans_sid'        => 0,
                'use_only_cookies'     => 1,
                'cookie_httponly'      => 1,
                'cookie_lifetime'      => 0,
                'cookie_path'          => '/',
                'cookie_domain'        => Config::getAplicationConfig()['App']['host'],
                'cookie_secure'        => Config::modeProduction(),
                'cookie_samesite'      => 'Lax',
                'use_strict_mode'      => Config::modeProduction(),

                // Caso queira só leitura:
                // 'read_and_close'     => true,
            ]);
        }
    }


    /**
     * Inserir uma chave e valor na sessão.
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $this->encrypt($value);
    }

    /**
     * Recuperar o valor de uma chave na sessão.
     * @param string $key
     * @return mixed
     */
    public function get(string $key): mixed
    {
        return isset($_SESSION[$key]) ? $this->decrypt($_SESSION[$key]) : null;
    }

    /**
     * Verifica se uma chave existe na sessão.
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    /**
     * Remove uma chave da sessão.
     * @param string $key
     * @return void
     */
    public function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    /**
     * Limpa todos os dados da sessão.
     * @return void
     */
    public function clear(): void
    {
        $_SESSION = [];
        setcookie(session_name(), '', time() - 3600, '/');
        session_destroy();
    }

    /**
     * Regenera o ID da sessão.
     * @return void
     */
    public function regenerate(): void
    {
        session_regenerate_id(true);
    }

    /**
     * Obtém o ID da sessão atual.
     * @return string
     */
    public function getId(): string
    {
        return session_id();
    }

    /**
     * Obtém todos os dados da sessão.
     * @return array
     */
    public function getAll(): array
    {
        $all = [];
        foreach ($_SESSION as $key => $value) {
            $all[$key] = $this->decrypt($value);
        }
        return $all;
    }

    /**
     * Cria configuração de sessões.
     * @param callable $callback
     * @return void
     */
    public function createConfig(callable $callback): void
    {
        if ($this->autostart) {
            return;
        }
        $callback();
    }

    /**
     * Criptografa um valor usando AES-256-CBC
     */
    private function encrypt(mixed $value): string
    {
        $serialized = serialize($value);
        $iv = random_bytes(16);
        $encrypted = openssl_encrypt($serialized, 'AES-256-CBC', $this->secret, 0, $iv);
        return base64_encode($iv . $encrypted);
    }

    /**
     * Descriptografa um valor
     */
    private function decrypt(string $data): mixed
    {
        $decoded = base64_decode($data);
        $iv = substr($decoded, 0, 16);
        $encrypted = substr($decoded, 16);
        $decrypted = openssl_decrypt($encrypted, 'AES-256-CBC', $this->secret, 0, $iv);
        return $decrypted !== false ? unserialize($decrypted) : null;
    }

    /**
     * Método para obter a instância da classe Luma.
     * @return Luma Retorna uma nova instância da classe Luma.
     */
    public function __debugInfo(): array
    {
        return [
            'Lumynus' => "Framework PHP"
        ];
    }
}
