<?php

declare(strict_types=1);

namespace Lumynus\Bundle\Framework;

use Lumynus\Bundle\Framework\LumaClasses;
use Lumynus\Bundle\Framework\LumynusTools;

abstract class DataBase extends LumaClasses
{
    use LumynusTools;

    /**
     * Pool de conexões abertas
     */
    protected static array $connections = [];

    /**
     * Cria ou reutiliza uma conexão
     *
     * @param bool $newConnection Força criação de nova conexão
     */
    public function connect(
        string $type,
        string $host,
        string $user,
        $password,
        string $dataBase,
        bool $newConnection = false
    ) {
        $type = strtolower($type);
        $key  = md5("$type|$host|$user|$dataBase");

        // Reutiliza conexão existente
        if (!$newConnection && isset(self::$connections[$key])) {
            return self::$connections[$key];
        }

        // Cria nova conexão
        $connection = match ($type) {
            'mysql', 'mariadb'  => $this->connectMySql($host, $user, $password, $dataBase),
            'postgresql'        => $this->connectPostgres($host, $user, $password, $dataBase),
            'sqlite'            => $this->connectSqlite($host),
            'sqlserver'         => $this->connectSqlServer($host, $user, $password, $dataBase),
            'ibase', 'firebird' => $this->connectFirebird($host, $user, $password, $dataBase),
            default             => throw new \InvalidArgumentException(
                "Database '{$type}' is not supported."
            )
        };

        return self::$connections[$key] = $connection;
    }

    /* =====================================================
       CONECTORES POR DRIVER
       ===================================================== */

    protected function connectMySql($h, $u, $p, $db)
    {
        if (extension_loaded('mysqli')) {
            return new \Mysql($h, $u, $p, $db);
        }

        if (extension_loaded('pdo_mysql')) {
            return new \PdoDriver('mysql', $h, $u, $p, $db);
        }

        throw new \RuntimeException(
            'No MySQL driver available. Expected extensions: mysqli or pdo_mysql'
        );
    }

    protected function connectPostgres($h, $u, $p, $db)
    {
        if (extension_loaded('pdo_pgsql')) {
            return new \PdoDriver('postgresql', $h, $u, $p, $db);
        }

        throw new \RuntimeException(
            'PostgreSQL driver unavailable. Required extension: pdo_pgsql'
        );
    }

    protected function connectSqlite($file)
    {
        if (extension_loaded('pdo_sqlite')) {
            return new \PdoDriver('sqlite', $file, null, null, null);
        }

        throw new \RuntimeException(
            'SQLite driver unavailable. Required extension: pdo_sqlite'
        );
    }

    protected function connectSqlServer($h, $u, $p, $db)
    {
        if (extension_loaded('pdo_sqlsrv')) {
            return new \PdoDriver('sqlserver', $h, $u, $p, $db);
        }

        throw new \RuntimeException(
            'Driver SQL Server unavailable. Required extension: pdo_sqlsrv'
        );
    }

    protected function connectFirebird($h, $u, $p, $db)
    {
        if (extension_loaded('ibase')) {
            return new \Ibase($h, $u, $p, $db);
        }

        if (extension_loaded('pdo_firebird')) {
            return new PdoDriver('firebird', $h, $u, $p, $db);
        }

        throw new \RuntimeException(
            'Firebird driver unavailable. Expected extensions: ibase or pdo_firebird'
        );
    }

    /* =====================================================
       GERENCIAMENTO DE CONEXÕES
       ===================================================== */

    /**
     * Fecha uma conexão específica pelos parâmetros
     */
    public static function closeConnection(
        string $type,
        string $host,
        string $user,
        string $dataBase
    ): bool {
        $type = strtolower($type);
        $key  = md5("$type|$host|$user|$dataBase");

        if (!isset(self::$connections[$key])) {
            return false;
        }

        $conn = self::$connections[$key];

        if (method_exists($conn, 'close')) {
            $conn->close();
        }

        unset(self::$connections[$key]);

        return true;
    }

    /**
     * Fecha uma conexão pela instância
     */
    public static function closeInstance(object $connection): bool
    {
        foreach (self::$connections as $key => $conn) {
            if ($conn === $connection) {

                if (method_exists($conn, 'close')) {
                    $conn->close();
                }

                unset(self::$connections[$key]);
                return true;
            }
        }

        return false;
    }

    /**
     * Fecha todas as conexões abertas
     */
    public static function closeAll(): void
    {
        foreach (self::$connections as $conn) {
            if (method_exists($conn, 'close')) {
                $conn->close();
            }
        }

        self::$connections = [];
    }

    /* =====================================================
       INSPEÇÃO DE AMBIENTE
       ===================================================== */

    /**
     * Retorna drivers disponíveis no PHP
     */
    public static function availableDrivers(): array
    {
        return [
            'mysqli'        => extension_loaded('mysqli'),
            'pdo_mysql'     => extension_loaded('pdo_mysql'),
            'pdo_pgsql'     => extension_loaded('pdo_pgsql'),
            'pdo_sqlite'    => extension_loaded('pdo_sqlite'),
            'pdo_sqlsrv'    => extension_loaded('pdo_sqlsrv'),
            'ibase'         => extension_loaded('ibase'),
            'pdo_firebird'  => extension_loaded('pdo_firebird'),
        ];
    }
}
