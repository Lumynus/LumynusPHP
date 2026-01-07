<?php

declare(strict_types=1);

namespace Lumynus\Bundle\Framework;

use Lumynus\Bundle\Framework\LumaClasses;
use Lumynus\Bundle\Framework\LumynusTools;

abstract class DataBase extends LumaClasses
{
    use LumynusTools;

    /**
     * Pool de conexões
     */
    protected static array $connections = [];

    /**
     * Cria ou reutiliza uma conexão
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

        // reutiliza do pool
        if (!$newConnection && isset(self::$connections[$key])) {
            return self::$connections[$key];
        }

        // cria nova
        $connection = match ($type) {
            'mysql', 'mariadb'  => new Mysql($host, $user, $password, $dataBase),

            'postgresql',
            'sqlite',
            'sqlserver'        => new PdoDriver($type, $host, $user, $password, $dataBase),

            'ibase',
            'firebird'         => new Ibase($host, $user, $password, $dataBase),

            default => throw new \InvalidArgumentException(
                "Unsupported database type: {$type}"
            )
        };

        return self::$connections[$key] = $connection;
    }

    /* =====================================================
       GERENCIAMENTO DO POOL (ESTÁTICO)
       ===================================================== */

    /**
     * Fecha uma conexão específica
     */
    public static function closeConnection(
        string $type,
        string $host,
        string $user,
        string $dataBase
    ): bool {
        $key = md5(strtolower("$type|$host|$user|$dataBase"));

        if (!isset(self::$connections[$key])) {
            return false;
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
        self::$connections = [];
    }

    /* =====================================================
       DEBUG / INSPEÇÃO
       ===================================================== */

    public static function poolSize(): int
    {
        return count(self::$connections);
    }
}


/**
 * Driver MySQLi
 */
class Mysql
{
    private $mysql;
    private $stmt;

    public function __construct($host, $user, $pass, $db)
    {
        $this->mysql = new \mysqli($host, $user, $pass, $db);
    }

    public function query(string $query, array $params = [], $types = ""): array|bool
    {
        $this->stmt = $this->mysql->prepare($query);
        if (!$this->stmt) throw new \RuntimeException($this->mysql->error);
        if (!empty($params)) {
            $t = is_array($types) ? implode('', $types) : $types;
            $this->stmt->bind_param($t ?: str_repeat('s', count($params)), ...$params);
        }
        $this->stmt->execute();
        $res = $this->stmt->get_result();
        return $res ? $res->fetch_all(MYSQLI_ASSOC) : true;
    }

    public function beginTransaction()
    {
        $this->mysql->begin_transaction();
    }
    public function commit()
    {
        $this->mysql->commit();
    }
    public function rollBack()
    {
        $this->mysql->rollback();
    }
    public function getInsertId(): int
    {
        return $this->mysql->insert_id;
    }
    public function getAffectedRows(): int
    {
        return $this->stmt->affected_rows;
    }

    public function __destruct()
    {
        if ($this->mysql) $this->mysql->close();
    }
}

/**
 * Driver PDO Generico
 */
class PdoDriver
{
    private \PDO $pdo;
    private ?\PDOStatement $stmt = null;

    public function __construct($type, $h, $u, $p, $db)
    {
        $dsn = match ($type) {
            'sqlite' => "sqlite:$h",
            'postgresql' => "pgsql:host=$h;dbname=$db",
            'sqlserver' => "sqlsrv:Server=$h;Database=$db",
        };

        $this->pdo = new \PDO(
            $dsn,
            $u,
            $p,
            [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            ]
        );
    }

    /** SELECT */
    public function fetchAll(string $sql, array $params = []): array
    {
        $this->stmt = $this->pdo->prepare($sql);
        $this->stmt->execute($params);

        return $this->stmt->fetchAll();
    }

    /** INSERT | UPDATE | DELETE */
    public function execute(string $sql, array $params = []): int
    {
        $this->stmt = $this->pdo->prepare($sql);
        $this->stmt->execute($params);

        return $this->stmt->rowCount();
    }

    public function lastInsertId(): int
    {
        return (int) $this->pdo->lastInsertId();
    }

    public function begin(): void
    {
        $this->pdo->beginTransaction();
    }

    public function commit(): void
    {
        $this->pdo->commit();
    }

    public function rollBack(): void
    {
        $this->pdo->rollBack();
    }
}


/**
 * Driver Ibase
 */
class Ibase
{
    private $conn;
    private $trans = null;

    public function __construct($h, $u, $p, $db)
    {
        $this->conn = \ibase_connect("$h:$db", $u, $p);
    }

    public function query($query, $params = [])
    {
        $target = $this->trans ?: $this->conn;
        $res = empty($params) ? \ibase_query($target, $query) : \ibase_execute(\ibase_prepare($target, $query), ...$params);
        if (is_resource($res)) {
            $rows = [];
            while ($row = \ibase_fetch_assoc($res)) $rows[] = $row;
            return $rows;
        }
        return true;
    }

    public function beginTransaction()
    {
        $this->trans = \ibase_trans($this->conn);
    }
    public function commit()
    {
        \ibase_commit($this->trans);
        $this->trans = null;
    }
    public function rollBack()
    {
        \ibase_rollback($this->trans);
        $this->trans = null;
    }
    public function getInsertId()
    { /* lógica do generator */
        return 0;
    }
    public function getAffectedRows()
    {
        return \ibase_affected_rows($this->conn);
    }

    public function __destruct()
    {
        if ($this->conn) \ibase_close($this->conn);
    }
}