<?php

declare(strict_types=1);

namespace Lumynus\Bundle\Framework;

use Lumynus\Bundle\Framework\LumaClasses;
use Lumynus\Bundle\Framework\LumynusTools;

/**
 * Classe base para conexões de banco de dados.
 */
abstract class DataBase extends LumaClasses
{
    use LumynusTools;

    public function connect(string $type, string $host, string $user, $password, string $dataBase)
    {
        switch (strtolower($type)) {
            case 'mysql':
            case 'mariadb':
                return new Mysql($host, $user, $password, $dataBase);
            case 'postgresql':
                return new PostergreSql($host, $user, $password, $dataBase);
            case 'sqlite':
                return new Sqlite($host);
            case 'sqlserver':
                return new SqlServer($host, $dataBase, $user, $password);
            case 'ibase':
            case 'firebird':
                return new Ibase($host, $user, $password, $dataBase);
            default:
                throw new \InvalidArgumentException("Unsupported database type: {$type}");
        }
    }
}

/**
 * MySQLi
 */
class Mysql
{
    private \mysqli $mysql;
    private string $charset = 'utf8mb4';
    private array $lastResult = [];

    public function __construct(string $host, string $user, $password, string $dataBase)
    {
        if (!class_exists('mysqli')) {
            throw new \RuntimeException("MySQLi extension is not available.");
        }
        $this->mysql = new \mysqli($host, $user, $password, $dataBase);
    }

    public function setCharset(string $charset): void
    {
        $this->charset = $charset;
        $this->mysql->set_charset($this->charset);
    }

    public function query(string $query, array $params = [], array $types = []): array|bool
    {
        $stmt = $this->mysql->prepare($query);
        if (!$stmt) throw new \RuntimeException("Prepare failed: " . $this->mysql->error);

        if (!empty($params)) $stmt->bind_param(implode('', $types), ...$params);
        if (!$stmt->execute()) throw new \RuntimeException("Execute failed: " . $stmt->error);

        $result = $stmt->get_result();
        $this->lastResult = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        return $this->lastResult ?: true;
    }

    public function getInsertId(): int
    {
        return $this->mysql->insert_id;
    }

    public function getAffectedRows(): int
    {
        return !empty($this->lastResult) ? count($this->lastResult) : $this->mysql->affected_rows;
    }
}

/**
 * PostgreSQL via PDO
 */
class PostergreSql
{
    private \PDO $pdo;
    private array $lastResult = [];

    public function __construct(string $host, string $user, $password, string $dataBase)
    {
        $this->pdo = new \PDO("pgsql:host=$host;dbname=$dataBase", $user, $password);
    }

    public function query(string $query, array $params = []): array
    {
        $stmt = $this->pdo->prepare($query);
        foreach ($params as $i => $v) $stmt->bindValue($i + 1, $v);
        if (!$stmt->execute()) throw new \RuntimeException("Execute failed: " . $this->pdo->errorInfo()[2]);

        $this->lastResult = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return $this->lastResult;
    }

    public function getInsertId(string $seqName = ''): int
    {
        return (int) $this->pdo->lastInsertId($seqName ?: null);
    }

    public function getAffectedRows(): int
    {
        return is_array($this->lastResult) ? count($this->lastResult) : 0;
    }
}

/**
 * SQLite via PDO
 */
class Sqlite
{
    private \PDO $pdo;
    private array $lastResult = [];

    public function __construct(string $file)
    {
        $this->pdo = new \PDO("sqlite:$file");
    }

    public function query(string $query, array $params = []): array
    {
        $stmt = $this->pdo->prepare($query);
        foreach ($params as $i => $v) $stmt->bindValue($i + 1, $v);
        $stmt->execute();

        $this->lastResult = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return $this->lastResult;
    }

    public function getInsertId(): int
    {
        return (int) $this->pdo->lastInsertId();
    }

    public function getAffectedRows(): int
    {
        return is_array($this->lastResult) ? count($this->lastResult) : 0;
    }
}

/**
 * SQL Server via PDO
 */
class SqlServer
{
    private \PDO $pdo;
    private array $lastResult = [];

    public function __construct(string $host, string $db, string $user, string $pass)
    {
        $this->pdo = new \PDO("sqlsrv:Server=$host;Database=$db", $user, $pass);
    }

    public function query(string $query, array $params = []): array
    {
        $stmt = $this->pdo->prepare($query);
        foreach ($params as $i => $v) $stmt->bindValue($i + 1, $v);
        $stmt->execute();

        $this->lastResult = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return $this->lastResult;
    }

    public function getInsertId(): int
    {
        return (int) $this->pdo->lastInsertId();
    }

    public function getAffectedRows(): int
    {
        return is_array($this->lastResult) ? count($this->lastResult) : 0;
    }
}

/**
 * InterBase / Firebird via ibase_* functions
 */
class Ibase
{
    private $conn;
    private array $lastResult = [];
    private $lastStmt = null;

    /**
     * Construtor
     */
    public function __construct(string $host, string $user, $password, string $database)
    {
        $this->conn = ibase_connect("$host:$database", $user, $password);
        if (!$this->conn) {
            throw new \RuntimeException("Cannot connect to InterBase/Firebird: " . ibase_errmsg());
        }
    }

    /**
     * Executa uma query com prepared statements
     */
    public function query(string $query, array $params = []): array
    {
        if (!empty($params)) {
            // Prepared statement
            $stmt = ibase_prepare($this->conn, $query);
            if (!$stmt) throw new \RuntimeException("Prepare failed: " . ibase_errmsg());

            $res = ibase_execute($stmt, ...$params);
            if (!$res) throw new \RuntimeException("Execute failed: " . ibase_errmsg());

            $this->lastStmt = $stmt;
        } else {
            // Query simples sem parâmetros
            $res = ibase_query($this->conn, $query);
            if (!$res) throw new \RuntimeException("Query failed: " . ibase_errmsg());
        }

        // Captura resultado
        $rows = [];
        while ($row = ibase_fetch_assoc($res)) {
            $rows[] = $row;
        }
        $this->lastResult = $rows;

        return $rows;
    }

    /**
     * Retorna o último ID gerado por um generator
     * Atenção: substitua "GEN_TABLE" pelo nome real do generator
     */
    public function getInsertId(string $generator = 'GEN_TABLE'): int
    {
        $res = ibase_query($this->conn, "SELECT GEN_ID($generator, 0) AS ID FROM RDB\$DATABASE");
        if (!$res) return 0;

        $row = ibase_fetch_assoc($res);
        return (int)($row['ID'] ?? 0);
    }

    /**
     * Retorna o número de linhas afetadas
     */
    public function getAffectedRows(): int
    {
        // Se SELECT retornou linhas, conta o array
        if (!empty($this->lastResult)) {
            return count($this->lastResult);
        }

        // Para INSERT/UPDATE/DELETE
        return ibase_affected_rows() ?: 0;
    }
}
