<?php

declare(strict_types=1);

namespace Lumynus\Bundle\Framework;

use Lumynus\Bundle\Framework\LumaClasses;

class Sessions extends LumaClasses
{

    private bool $autostart = true;

    /**
     * Constructor to initialize the session.
     */
    public function __construct(bool $autostart = true)
    {
        $this->autostart = $autostart;
        if (!$autostart) {
            return;
        }
        if (session_status() === PHP_SESSION_NONE) {
            session_name('LumynusSession');

            ini_set('session.use_trans_sid', 0);
            ini_set('session.use_only_cookies', 1);
            ini_set('session.cookie_httponly', 1);

            session_set_cookie_params([
                'lifetime' => 0,
                'path' => '/',
                'secure' => Config::modeProduction(),
                'httponly' => Config::modeProduction(),
                'samesite' => 'Strict'
            ]);

            session_start();


            if (!isset($_SESSION['ip_address']) || !isset($_SESSION['user_agent'])) {
                $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'];
                $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
            } elseif (
                $_SESSION['ip_address'] !== $_SERVER['REMOTE_ADDR'] ||
                $_SESSION['user_agent'] !== $_SERVER['HTTP_USER_AGENT']
            ) {
                session_unset();
                session_destroy();
                exit;
            }
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
        $_SESSION[$key] = $value;
    }

    /**
     * Recuperar o valor de uma chave na sessão.
     * @param string $key
     * @return mixed
     */
    public function get(string $key): mixed
    {
        return $_SESSION[$key] ?? null;
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
        return $_SESSION;
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
