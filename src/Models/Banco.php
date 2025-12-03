<?php

namespace App\Models;

use Lumynus\Bundle\Framework\DataBase;

class Banco extends DataBase
{
    private $banco;
    protected string $table = '';
    protected string $alias = ''; // Será definida nas classes filhas

    protected function __construct()
    {
        $dados = $this->encrypt()->readFiles(
            [
                'bd_banco',
                'bd_host',
                'bd_pass',
                'bd_user'
            ],
            'qrmidia'
        );

        $this->banco = $this->connect(
            'mysql',
            $dados['bd_host'],
            $dados['bd_user'],
            $dados['bd_pass'],
            $dados['bd_banco']
        );
    }

    /**
     * Executa uma query com prepared statements
     * 
     * @param string $query SQL query com placeholders
     * @param array $params Parâmetros para bind
     * @param array $types Tipos dos parâmetros (opcional, será auto-detectado se vazio)
     * @return mixed Resultado da query
     */
    protected function query(string $query, array $params = [], array $types = [])
    {
        // Se os tipos não foram especificados, detecta automaticamente
        if (empty($types) && !empty($params)) {
            $types = $this->detectParamTypes($params);
        }

        return $this->banco->query($query, $params, $types);
    }

    /**
     * Detecta automaticamente os tipos dos parâmetros
     * 
     * @param array $params Array de parâmetros
     * @return array Array de tipos para o framework Lumynus
     */
    private function detectParamTypes(array $params): array
    {
        $types = [];

        foreach ($params as $param) {
            if (is_int($param)) {
                $types[] = 'i'; // integer
            } elseif (is_float($param) || is_double($param)) {
                $types[] = 'd'; // double
            } elseif (is_string($param)) {
                $types[] = 's'; // string
            } else {
                $types[] = 's'; // default para string
            }
        }

        return $types;
    }

    /**
     * Seleciona registros com filtros, joins e ordenação
     * 
     * @param array $filters Filtros WHERE no formato ['coluna' => 'valor']
     * @param string $columns Colunas a selecionar
     * @param string $joins Cláusulas JOIN (ex: 'LEFT JOIN tabela ON condicao')
     * @param string $orderBy Ordenação (ex: 'coluna ASC')
     * @param int|null $limit Limite de registros
     * @param int $offset Offset para paginação
     * @return mixed Resultado da consulta
     */
    protected function select(
        array $filters = [],
        string $columns = '*',
        string $joins = '',
        string $orderBy = '',
        ?int $limit = null,
        int $offset = 0
    ) {
        $sql = "SELECT {$columns} FROM {$this->table} {$this->alias}";
        $params = [];

        // Adiciona JOINs
        if (!empty($joins)) {
            $sql .= " {$joins}";
        }

        // Adiciona WHERE se houver filtros
        if (!empty($filters)) {
            $whereConditions = [];
            foreach ($filters as $key => $value) {
                if ($value === null || $value === '') {
                    continue; // ignora filtros vazios
                }

                if (stripos($key, 'LIKE') !== false) {
                    // Exemplo: ['nome LIKE' => 'João']
                    $col = trim(str_ireplace('LIKE', '', $key));
                    $whereConditions[] = "{$col} LIKE ?";
                    $params[] = "%{$value}%";
                } elseif (stripos($key, 'BETWEEN') !== false && is_array($value) && count($value) === 2) {
                    // Exemplo: ['data BETWEEN' => ['2024-01-01', '2024-12-31']]
                    $col = trim(str_ireplace('BETWEEN', '', $key));
                    $whereConditions[] = "{$col} BETWEEN ? AND ?";
                    $params[] = $value[0];
                    $params[] = $value[1];
                } elseif (stripos($key, 'IN') !== false && is_array($value) && count($value) > 0) {
                    // Exemplo: ['id IN' => [1, 2, 3]]
                    $col = trim(str_ireplace('IN', '', $key));
                    $placeholders = implode(',', array_fill(0, count($value), '?'));
                    $whereConditions[] = "{$col} IN ({$placeholders})";
                    $params = array_merge($params, $value);
                } else {
                    // Padrão: igualdade
                    $whereConditions[] = "{$key} = ?";
                    $params[] = $value;
                }
            }
            $sql .= " WHERE " . implode(' AND ', $whereConditions);
        }

        // Adiciona ORDER BY se especificado
        if (!empty($orderBy)) {
            $sql .= " ORDER BY {$orderBy}";
        }

        // Adiciona LIMIT e OFFSET se especificado
        if ($limit !== null) {
            $sql .= " LIMIT {$limit}";
            if ($offset > 0) {
                $sql .= " OFFSET {$offset}";
            }
        }

        $result = $this->query($sql, $params);
        return is_array($result) ? $result : [];
    }


    /**
     * Busca um registro por ID
     * 
     * @param int $id ID do registro
     * @return mixed Resultado da consulta
     */
    protected function findById(int $id)
    {
        $sql = "SELECT * FROM {$this->table} WHERE id = ?";
        return $this->query($sql, [$id]);
    }

    /**
     * Busca um único registro por campo específico
     * 
     * @param string $column Nome da coluna
     * @param mixed $value Valor a buscar
     * @return mixed Resultado da consulta
     */
    protected function findBy(string $column, $value)
    {
        $sql = "SELECT * FROM {$this->table} WHERE {$column} = ? LIMIT 1";
        return $this->query($sql, [$value]);
    }

    /**
     * Busca múltiplos registros por campo específico
     * 
     * @param string $column Nome da coluna
     * @param mixed $value Valor a buscar
     * @param string $orderBy Ordenação opcional
     * @param int|null $limit Limite de registros
     * @return mixed Resultado da consulta
     */
    protected function findAllBy(string $column, $value, string $orderBy = '', ?int $limit = null)
    {
        $sql = "SELECT * FROM {$this->table} WHERE {$column} = ?";

        if (!empty($orderBy)) {
            $sql .= " ORDER BY {$orderBy}";
        }

        if ($limit !== null) {
            $sql .= " LIMIT {$limit}";
        }

        return $this->query($sql, [$value]);
    }

    /**
     * Insere um novo registro
     * 
     * @param array $data Dados no formato ['coluna' => 'valor']
     * @return mixed Resultado da inserção
     */
    protected function insert(array $data)
    {
        if (empty($data)) {
            return false;
        }

        $columns = array_keys($data);
        $placeholders = str_repeat('?,', count($data) - 1) . '?';
        $values = array_values($data);

        $sql = "INSERT INTO {$this->table} (" . implode(',', $columns) . ") VALUES ({$placeholders})";

        return $this->query($sql, $values);
    }

    /**
     * Insere um registro e retorna o ID gerado
     * 
     * @param array $data Dados no formato ['coluna' => 'valor']
     * @return int|false ID do registro inserido ou false em caso de falha
     */
    protected function insertReturnID(array $data)
    {
        if (empty($data)) {
            return false;
        }

        $columns = array_keys($data);
        $placeholders = str_repeat('?,', count($data) - 1) . '?';
        $values = array_values($data);

        $sql = "INSERT INTO {$this->table} (" . implode(',', $columns) . ") VALUES ({$placeholders})";

        // Executa a query
        $this->query($sql, $values);

        // Retorna o ID do último registro inserido
        return $this->banco->getInsertId();
    }


    /**
     * Insere múltiplos registros de uma vez
     * 
     * @param array $data Array de arrays com dados
     * @return mixed Resultado da inserção
     */
    protected function insertBatch(array $data)
    {
        if (empty($data)) {
            return false;
        }

        $columns = array_keys($data[0]);
        $placeholders = '(' . str_repeat('?,', count($columns) - 1) . '?)';
        $allPlaceholders = str_repeat($placeholders . ',', count($data) - 1) . $placeholders;

        $values = [];
        foreach ($data as $row) {
            $values = array_merge($values, array_values($row));
        }

        $sql = "INSERT INTO {$this->table} (" . implode(',', $columns) . ") VALUES {$allPlaceholders}";

        return $this->query($sql, $values);
    }

    /**
     * Atualiza um registro por ID
     * 
     * @param int $id ID do registro
     * @param array $data Dados a atualizar no formato ['coluna' => 'valor']
     * @return mixed Resultado da atualização
     */
    protected function update(int $id, array $data)
    {
        if (empty($data)) {
            return false;
        }

        $setClause = [];
        $params = [];

        foreach ($data as $column => $value) {
            $setClause[] = "{$column} = ?";
            $params[] = $value;
        }

        $params[] = $id;

        $sql = "UPDATE {$this->table} SET " . implode(', ', $setClause) . " WHERE id = ?";

        return $this->query($sql, $params);
    }

    /**
     * Atualiza registros por condições específicas
     * 
     * @param array $conditions Condições WHERE no formato ['coluna' => 'valor']
     * @param array $data Dados a atualizar no formato ['coluna' => 'valor']
     * @return mixed Resultado da atualização
     */
    protected function updateWhere(array $conditions, array $data)
    {
        if (empty($data) || empty($conditions)) {
            return false;
        }

        $setClause = [];
        $whereClause = [];
        $params = [];

        // Monta o SET
        foreach ($data as $column => $value) {
            $setClause[] = "{$column} = ?";
            $params[] = $value;
        }

        // Monta o WHERE
        foreach ($conditions as $column => $value) {
            $whereClause[] = "{$column} = ?";
            $params[] = $value;
        }

        $sql = "UPDATE {$this->table} SET " . implode(', ', $setClause) . " WHERE " . implode(' AND ', $whereClause);

        return $this->query($sql, $params);
    }

    /**
     * Deleta um registro por ID
     * 
     * @param int $id ID do registro
     * @return mixed Resultado da deleção
     */
    protected function delete(int $id)
    {
        $sql = "DELETE FROM {$this->table} WHERE id = ?";
        return $this->query($sql, [$id]);
    }

    /**
     * Deleta registros por condições
     * 
     * @param array $conditions Condições WHERE no formato ['coluna' => 'valor']
     * @return mixed Resultado da deleção
     */
    protected function deleteWhere(array $conditions)
    {
        if (empty($conditions)) {
            return false;
        }

        $whereClause = [];
        $params = [];

        foreach ($conditions as $column => $value) {
            $whereClause[] = "{$column} = ?";
            $params[] = $value;
        }

        $sql = "DELETE FROM {$this->table} WHERE " . implode(' AND ', $whereClause);

        return $this->query($sql, $params);
    }

    /**
     * Conta registros na tabela
     * 
     * @param array $conditions Condições WHERE opcionais no formato ['coluna' => 'valor']
     * @param string $joins JOINs opcionais
     * @return mixed Resultado da contagem
     */
    protected function count(array $conditions = [], string $joins = '')
    {
        $sql = "SELECT COUNT(*) as total FROM {$this->table}";
        $params = [];

        // Adiciona JOINs
        if (!empty($joins)) {
            $sql .= " {$joins}";
        }

        if (!empty($conditions)) {
            $whereClause = [];
            foreach ($conditions as $column => $value) {
                $whereClause[] = "{$column} = ?";
                $params[] = $value;
            }
            $sql .= " WHERE " . implode(' AND ', $whereClause);
        }

        return $this->query($sql, $params);
    }

    /**
     * Verifica se um registro existe
     * 
     * @param array $conditions Condições no formato ['coluna' => 'valor']
     * @return bool True se existe, false caso contrário
     */
    protected function exists(array $conditions)
    {
        $result = $this->count($conditions);
        return $result && isset($result[0]['total']) && $result[0]['total'] > 0;
    }

    /**
     * Executa query personalizada com prepared statements
     * 
     * @param string $sql Query SQL com placeholders
     * @param array $params Parâmetros para bind
     * @return mixed Resultado da query
     */
    protected function customQuery(string $sql, array $params = [])
    {
        return $this->query($sql, $params);
    }

    /**
     * Busca registros com condições avançadas (IN, BETWEEN, LIKE)
     * 
     * @param array $conditions Condições complexas
     * @param string $columns Colunas a selecionar
     * @param string $joins JOINs opcionais
     * @param string $orderBy Ordenação
     * @param int|null $limit Limite
     * @return mixed Resultado da consulta
     * 
     * Exemplo: 
     * $conditions = [
     *     'status' => ['IN', [1, 2, 3]],
     *     'created_at' => ['BETWEEN', ['2023-01-01', '2023-12-31']],
     *     'name' => ['LIKE', '%João%']
     * ];
     */
    protected function findWhere(array $conditions, string $columns = '*', string $joins = '', string $orderBy = '', ?int $limit = null)
    {
        $sql = "SELECT {$columns} FROM {$this->table}";
        $params = [];

        // Adiciona JOINs
        if (!empty($joins)) {
            $sql .= " {$joins}";
        }

        if (!empty($conditions)) {
            $whereClause = [];

            foreach ($conditions as $column => $condition) {
                if (is_array($condition)) {
                    $operator = $condition[0];
                    $value = $condition[1];

                    switch (strtoupper($operator)) {
                        case 'IN':
                            $placeholders = str_repeat('?,', count($value) - 1) . '?';
                            $whereClause[] = "{$column} IN ({$placeholders})";
                            $params = array_merge($params, $value);
                            break;

                        case 'BETWEEN':
                            $whereClause[] = "{$column} BETWEEN ? AND ?";
                            $params[] = $value[0];
                            $params[] = $value[1];
                            break;

                        case 'LIKE':
                            $whereClause[] = "{$column} LIKE ?";
                            $params[] = $value;
                            break;

                        default:
                            $whereClause[] = "{$column} {$operator} ?";
                            $params[] = $value;
                    }
                } else {
                    $whereClause[] = "{$column} = ?";
                    $params[] = $condition;
                }
            }

            $sql .= " WHERE " . implode(' AND ', $whereClause);
        }

        // Adiciona ORDER BY
        if (!empty($orderBy)) {
            $sql .= " ORDER BY {$orderBy}";
        }

        // Adiciona LIMIT
        if ($limit !== null) {
            $sql .= " LIMIT {$limit}";
        }

        return $this->query($sql, $params);
    }
}
