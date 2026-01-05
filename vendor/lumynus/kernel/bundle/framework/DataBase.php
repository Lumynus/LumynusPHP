<?php

declare(strict_types=1);

namespace Lumynus\Bundle\Framework;

use Lumynus\Bundle\Framework\LumaClasses;
use Lumynus\Bundle\Framework\LumynusTools;

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
            case 'sqlite':
            case 'sqlserver':
                return new PdoDriver($type, $host, $user, $password, $dataBase);
            case 'ibase':
            case 'firebird':
                return new Ibase($host, $user, $password, $dataBase);
            default:
                throw new \InvalidArgumentException("Unsupported database type: {$type}");
        }
    }
}

/**
 * Driver MySQLi
 */
class Mysql
{
    private $mysql;

    public function __construct($host, $user, $pass, $db) {
        $this->mysql = new \mysqli($host, $user, $pass, $db);
    }

    public function query(string $query, array $params = [], $types = ""): array|bool {
        $stmt = $this->mysql->prepare($query);
        if (!$stmt) throw new \RuntimeException($this->mysql->error);
        if (!empty($params)) {
            $t = is_array($types) ? implode('', $types) : $types;
            $stmt->bind_param($t ?: str_repeat('s', count($params)), ...$params);
        }
        $stmt->execute();
        $res = $stmt->get_result();
        return $res ? $res->fetch_all(MYSQLI_ASSOC) : true;
    }

    public function beginTransaction() { $this->mysql->begin_transaction(); }
    public function commit() { $this->mysql->commit(); }
    public function rollBack() { $this->mysql->rollback(); }
    public function getInsertId(): int { return $this->mysql->insert_id; }

    public function __destruct() {
        if ($this->mysql) $this->mysql->close();
    }
}

/**
 * Driver PDO Generico
 */
class PdoDriver
{
    private $pdo;

    public function __construct($type, $h, $u, $p, $db) {
        $dsn = match($type) {
            'sqlite' => "sqlite:$h",
            'postgresql' => "pgsql:host=$h;dbname=$db",
            'sqlserver' => "sqlsrv:Server=$h;Database=$db",
        };
        $this->pdo = new \PDO($dsn, $u, $p, [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]);
    }

    public function query(string $query, array $params = []): array|bool {
        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);
        return $stmt->columnCount() > 0 ? $stmt->fetchAll(\PDO::FETCH_ASSOC) : true;
    }

    public function beginTransaction() { $this->pdo->beginTransaction(); }
    public function commit() { $this->pdo->commit(); }
    public function rollBack() { $this->pdo->rollBack(); }
    public function getInsertId(): int { return (int)$this->pdo->lastInsertId(); }

    public function __destruct() { $this->pdo = null; }
}

/**
 * Driver Ibase
 */
class Ibase
{
    private $conn;
    private $trans = null;

    public function __construct($h, $u, $p, $db) {
        $this->conn = \ibase_connect("$h:$db", $u, $p);
    }

    public function query($query, $params = []) {
        $target = $this->trans ?: $this->conn;
        $res = empty($params) ? \ibase_query($target, $query) : \ibase_execute(\ibase_prepare($target, $query), ...$params);
        if (is_resource($res)) {
            $rows = [];
            while ($row = \ibase_fetch_assoc($res)) $rows[] = $row;
            return $rows;
        }
        return true;
    }

    public function beginTransaction() { $this->trans = \ibase_trans($this->conn); }
    public function commit() { \ibase_commit($this->trans); $this->trans = null; }
    public function rollBack() { \ibase_rollback($this->trans); $this->trans = null; }
    public function getInsertId() { /* lógica do generator */ return 0; }

    public function __destruct() { if ($this->conn) \ibase_close($this->conn); }
}
