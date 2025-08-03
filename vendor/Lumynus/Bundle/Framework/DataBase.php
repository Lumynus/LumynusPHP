<?php

declare(strict_types=1);

namespace Lumynus\Bundle\Framework;

use Lumynus\Bundle\Framework\LumaClasses;

/**
 * Classe base para conexões de banco de dados.
 */
abstract class DataBase extends LumaClasses
{


    /**
     * @param string $type Tipo de banco de dados (mysql, postgresql, sqlite, sqlserver)
     * @param string $host Host do banco de dados
     * @param string $user Usuário do banco de dados
     * @param string $password Senha do banco de dados
     * @param string $dataBase Nome do banco de dados
     */
    public function __construct(string $type, string $host, string $user, $password, string $dataBase)
    {
        switch ($type) {
            case 'mysql':
                return new Mysql($host, $user, $password, $dataBase);
            case 'mariadb':
                return new Mysql($host, $user, $password, $dataBase);
            case 'postgresql':
                return new PostergreSql($host, $user, $password, $dataBase);
            case 'sqlite':
                return new Sqlite($host);
            case 'sqlserver':
                return new SqlServer($host, $dataBase, $user, $password);
            default:
                throw new \InvalidArgumentException("Unsupported database type: {$type}");
        }
    }
}

/**
 * Classe de conexão e execução de queries para MySQL com MySQLi.
 */
class Mysql
{
    private \mysqli $mysql;
    private string $charset = 'utf8mb4';

    /**
     * @param string $host
     * @param string $user
     * @param string $password
     * @param string $dataBase
     */
    public function __construct(string $host, string $user, $password, string $dataBase)
    {
        if (!class_exists('mysqli')) {
            throw new \RuntimeException("MySQLi extension is not available.");
        }
        $this->mysql = new \mysqli($host, $user, $password, $dataBase);
    }

    public function __destruct()
    {
        if ($this->mysql) {
            $this->mysql->close();
        }
    }

    /**
     * @param string $charset
     */
    public function setCharset(string $charset): void
    {
        $this->charset = $charset;
        $this->mysql->set_charset($this->charset);
    }

    /**
     * @param string $query
     * @param array $params
     * @param array $types
     * @return mixed
     */
    public function query(string $query, array $params = [], array $types = []): mixed
    {
        if ($this->mysql->connect_errno) {
            throw new \RuntimeException("Connection failed: " . $this->mysql->connect_error);
        }

        $stmt = $this->mysql->prepare($query);
        if (!$stmt) {
            throw new \RuntimeException("Prepare failed: " . $this->mysql->error);
        }

        if (!empty($params)) {
            $stmt->bind_param(implode('', $types), ...$params);
        }

        if (!$stmt->execute()) {
            throw new \RuntimeException("Execute failed: " . $stmt->error);
        }

        $result = $stmt->get_result();
        return $result === false ? true : $result->fetch_all(MYSQLI_ASSOC);
    }

    public function __debugInfo()
    {
        return ['LumynusBD' => 'DataBase'];
    }
}

/**
 * Classe para conexão e execução de queries com PostgreSQL via PDO.
 */
class PostergreSql
{
    private \PDO $pdo;

    /**
     * @param string $host
     * @param string $user
     * @param string $password
     * @param string $dataBase
     */
    public function __construct(string $host, string $user, $password, string $dataBase)
    {
        if (!class_exists('PDO')) {
            throw new \RuntimeException("PDO extension is not available.");
        }
        $this->pdo = new \PDO("pgsql:host=$host;dbname=$dataBase", $user, $password);
    }

    /**
     * @param string $query
     * @param array $params
     * @return mixed
     */
    public function query(string $query, array $params = []): mixed
    {
        $stmt = $this->pdo->prepare($query);
        if (!$stmt) {
            throw new \RuntimeException("Prepare failed: " . $this->pdo->errorInfo()[2]);
        }

        foreach ($params as $key => $value) {
            $stmt->bindValue($key + 1, $value);
        }

        if (!$stmt->execute()) {
            throw new \RuntimeException("Execute failed: " . $stmt->errorInfo()[2]);
        }

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}

/**
 * Classe para conexão e execução de queries com SQLite via PDO.
 */
class Sqlite
{
    private \PDO $pdo;

    /**
     * @param string $file Caminho para o arquivo SQLite
     */
    public function __construct(string $file)
    {
        $this->pdo = new \PDO("sqlite:$file");
    }

    public function query(string $query, array $params = []): mixed
    {
        $stmt = $this->pdo->prepare($query);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key + 1, $value);
        }
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}

/**
 * Classe para conexão com SQL Server via PDO.
 */
class SqlServer
{
    private \PDO $pdo;

    /**
     * @param string $host
     * @param string $db
     * @param string $user
     * @param string $pass
     */
    public function __construct(string $host, string $db, string $user, string $pass)
    {
        $this->pdo = new \PDO("sqlsrv:Server=$host;Database=$db", $user, $pass);
    }

    public function query(string $query, array $params = []): mixed
    {
        $stmt = $this->pdo->prepare($query);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key + 1, $value);
        }
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
