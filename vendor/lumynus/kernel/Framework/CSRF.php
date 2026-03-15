<?php

declare(strict_types=1);

/**
 * @author Weleny Santos <welenysantos@gmail.com>
 * @package Lumynus\Framework
 */

namespace Lumynus\Framework;

use Lumynus\Framework\LumaClasses;
use Lumynus\Framework\Sessions;
use Lumynus\Framework\Config;

final class CSRF extends LumaClasses
{
    /**
     * @var Sessions Instância da sessão compartilhada.
     */
    private static ?Sessions $session = null;

    /**
     * Retorna a instância única da sessão.
     *
     * @return Sessions
     */
    private static function session(): Sessions
    {
        if (self::$session === null) {
            self::$session = new Sessions();
        }
        return self::$session;
    }

    /**
     * Gera e armazena um token CSRF na sessão.
     *
     * @return string O token CSRF gerado.
     */
    public static function generateToken(): string
    {
        $token = bin2hex(random_bytes(32));
        self::session()->set(
            Config::getApplicationConfig()['security']['csrf']['nameToken'],
            $token
        );
        return $token;
    }

    /**
     * Verifica se o token CSRF é válido.
     *
     * @param string $token O token CSRF a ser verificado.
     * @return bool Retorna true se o token for válido, caso contrário, false.
     */
    public static function isValidToken(string $token): bool
    {
        if (empty($token) || !ctype_xdigit($token) || strlen($token) !== 64) {
            return false;
        }

        $name = Config::getApplicationConfig()['security']['csrf']['nameToken'];

        if (!self::session()->has($name)) {
            return false;
        }

        $sessionToken = self::session()->get($name);
        return hash_equals($sessionToken, $token);
    }

    /**
     * Recupera Token salvo
     *
     * @return string O token CSRF gerado.
     */
    public static function getToken(): string|null
    {
        return
            self::session()->get(
                Config::getApplicationConfig()['security']['csrf']['nameToken']
            ) ?? null;
    }
}
