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
     *
     * [
     *   key => [
     *     'connection' => object,
     *     'identifier' => string,
     *     'type'       => string,
     *     'host'       => string,
     *     'user'       => string,
     *     'database'   => string,
     *   ]
     * ]
     */
    protected static array $connections = [];

    /**
     * Cria ou reutiliza uma conexão
     */
    public function connect(
        string $type,
        string $host,
        string $user,
        mixed $password,
        string $dataBase,
        bool $newConnection = false,
        string $connectionIdentifier = 'default'
    ): object {
        $type = strtolower($type);

        $key = self::makeKey(
            $type,
            $host,
            $user,
            $dataBase,
            $connectionIdentifier
        );

        // reutiliza do pool
        if (!$newConnection && isset(self::$connections[$key])) {
            return self::$connections[$key]['connection'];
        }

        // cria nova conexão
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

        self::$connections[$key] = [
            'connection' => $connection,
            'identifier' => $connectionIdentifier,
            'type'       => $type,
            'host'       => $host,
            'user'       => $user,
            'database'   => $dataBase,
        ];

        return $connection;
    }

    /**
     * Gera a chave única da conexão
     */
    protected static function makeKey(
        string $type,
        string $host,
        string $user,
        string $dataBase,
        string $connectionIdentifier
    ): string {
        return md5(strtolower(
            "{$type}|{$host}|{$user}|{$dataBase}|{$connectionIdentifier}"
        ));
    }

    /* =====================================================
       GERENCIAMENTO DO POOL
       ===================================================== */

    /**
     * Fecha uma conexão específica
     */
    public static function closeConnection(
        string $type,
        string $host,
        string $user,
        string $dataBase,
        string $connectionIdentifier
    ): bool {
        $key = self::makeKey(
            strtolower($type),
            $host,
            $user,
            $dataBase,
            $connectionIdentifier
        );

        if (!isset(self::$connections[$key])) {
            return false;
        }

        unset(self::$connections[$key]);
        return true;
    }

    /**
     * Fecha todas as conexões associadas a um identificador
     */
    public static function closeByIdentifier(string $connectionIdentifier): int
    {
        $closed = 0;

        foreach (self::$connections as $key => $data) {
            if ($data['identifier'] === $connectionIdentifier) {
                unset(self::$connections[$key]);
                $closed++;
            }
        }

        return $closed;
    }

    /**
     * Fecha uma conexão pela instância
     */
    public static function closeInstance(object $connection): bool
    {
        foreach (self::$connections as $key => $data) {
            if ($data['connection'] === $connection) {
                unset(self::$connections[$key]);
                return true;
            }
        }

        return false;
    }

    /**
     * Fecha todas as conexões abertas
     *
     * Use apenas no shutdown da aplicação
     */
    public static function closeAll(): void
    {
        if ((Config::getAplicationConfig()['database']['autoClose'] ?? true) !== true) {
            return;
        }
        self::$connections = [];
    }

    /* =====================================================
       DEBUG / INSPEÇÃO
       ===================================================== */

    public static function poolSize(): int
    {
        return count(self::$connections);
    }

    public static function identifiers(): array
    {
        return array_values(array_unique(
            array_column(self::$connections, 'identifier')
        ));
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
    public function getThreadId(): int
    {
        return $this->mysql->thread_id;
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

    public function getThreadId(): ?int
    {
        try {
            $driver = $this->pdo->getAttribute(\PDO::ATTR_DRIVER_NAME);

            return match ($driver) {
                'mysql' => (int) $this->pdo
                    ->query('SELECT CONNECTION_ID()')
                    ->fetchColumn(),

                'pgsql' => (int) $this->pdo
                    ->query('SELECT pg_backend_pid()')
                    ->fetchColumn(),

                'sqlsrv' => (int) $this->pdo
                    ->query('SELECT @@SPID')
                    ->fetchColumn(),

                'sqlite' => null,

                default => null,
            };
        } catch (\Throwable) {
            return null;
        }
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

    public function getThreadId(): ?int
    {
        $info = \ibase_db_info($this->conn, IBASE_STMT_ID);
        return $info !== false ? (int) $info : null;
    }
}
