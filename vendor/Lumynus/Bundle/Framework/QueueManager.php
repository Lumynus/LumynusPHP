<?php

declare(strict_types=1);

namespace Lumynus\Bundle\Framework;

use Lumynus\Bundle\Framework\LumaClasses;
use Lumynus\Bundle\Framework\Config;

class QueueManager extends LumaClasses
{

    /**
     * Diretório onde os arquivos de fila NDJSON serão armazenados.
     * Pode ser configurado conforme necessário.
     */
    private string $queueDir = '';

    public function __construct()
    {
        $this->queueDir = Config::pathProject() .
            DIRECTORY_SEPARATOR .
            'vendor' .  DIRECTORY_SEPARATOR .
            'Lumynus' .  DIRECTORY_SEPARATOR .
            'Memory' .  DIRECTORY_SEPARATOR .
            'queues' .  DIRECTORY_SEPARATOR;
    }

    /**
     * Insere um novo item no final da fila NDJSON.
     *
     * @param array $arrayToJson Array associativo a ser adicionado.
     * @param string $file Caminho do arquivo NDJSON.
     * @return bool True se sucesso, False se erro ou se já estiver em execução.
     */
    public function insert(array $arrayToJson, string $file): bool
    {
        if (empty($arrayToJson)) {
            return false;
        }
        foreach ($arrayToJson as $item) {
            if (is_array($item)) {
                return false;
            }
        }
        if (!str_contains($file, '.ndjson')) {
            $file = str_replace('.json', '.ndjson', $file);
        }

        $lockFile = $file . '.lock';
        if (!$this->acquireLock($lockFile)) {
            return false;
        }

        $file = $this->queueDir . $file;

        try {
            $jsonLine = json_encode($arrayToJson, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION);
            file_put_contents($file, $jsonLine . PHP_EOL, FILE_APPEND | LOCK_EX);
            return true;
        } catch (\Throwable $e) {
            return false;
        } finally {
            $this->releaseLock($lockFile);
        }
    }

    /**
     * Remove linhas que contenham uma chave com determinado valor.
     *
     * @param string $file Caminho do arquivo NDJSON.
     * @param string $key Nome da chave.
     * @param mixed $value Valor correspondente.
     * @return bool True se sucesso, False se erro ou bloqueado.
     */
    public function remove(string $file, string $key, $value): bool
    {
        if (!file_exists($file)) {
            return false;
        }
        $lockFile = $file . '.lock';
        if (!$this->acquireLock($lockFile)) {
            return false;
        }
        if (!str_contains($file, '.ndjson')) {
            $file = str_replace('.json', '.ndjson', $file);
        }
        $file = $this->queueDir . $file;
        try {
            $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $newLines = [];

            foreach ($lines as $line) {
                $decoded = json_decode($line, true);
                if (!is_array($decoded) || !array_key_exists($key, $decoded) || $decoded[$key] !== $value) {
                    $newLines[] = $line;
                }
            }

            file_put_contents($file, implode(PHP_EOL, $newLines) . PHP_EOL, LOCK_EX);
            return true;
        } catch (\Throwable $e) {
            return false;
        } finally {
            $this->releaseLock($lockFile);
        }
    }

    /**
     * Atualiza linhas com base em uma chave e valor, mesclando com os dados novos.
     *
     * @param string $file Caminho do arquivo NDJSON.
     * @param string $key Chave a ser buscada.
     * @param mixed $value Valor da chave.
     * @param array $newData Novos dados para mesclar.
     * @return bool True se sucesso, False se erro ou bloqueado.
     */
    public function update(string $file, string $key, $value, array $newData): bool
    {
        if (!file_exists($file)) {
            return false;
        }

        $lockFile = $file . '.lock';
        if (!$this->acquireLock($lockFile)) {
            return false;
        }

        if (!str_contains($file, '.ndjson')) {
            $file = str_replace('.json', '.ndjson', $file);
        }

        try {
            $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $updatedLines = [];

            foreach ($lines as $line) {
                $decoded = json_decode($line, true);

                if (!is_array($decoded)) {
                    $updatedLines[] = $line;
                    continue;
                }

                if (!array_key_exists($key, $decoded) || $decoded[$key] !== $value) {
                    $updatedLines[] = $line;
                    continue;
                }

                $decoded = array_merge($decoded, $newData);
                $updatedLines[] = json_encode($decoded, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION);
            }
            $file = $this->queueDir . $file;
            file_put_contents($file, implode(PHP_EOL, $updatedLines) . PHP_EOL, LOCK_EX);
            return true;
        } catch (\Throwable $e) {
            return false;
        } finally {
            $this->releaseLock($lockFile);
        }
    }

    /**
     * Retorna todos os itens da fila como array associativo.
     *
     * @param string $file Caminho do arquivo NDJSON.
     * @return array Retorna um array de arrays.
     */
    public function getAsArray(string $file): array
    {
        if (!file_exists($file)) {
            return [];
        }

        if (!str_contains($file, '.ndjson')) {
            $file = str_replace('.json', '.ndjson', $file);
        }
        $file = $this->queueDir . $file;
        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $items = [];

        foreach ($lines as $line) {
            $decoded = json_decode($line, true); // como array
            if (is_array($decoded)) {
                $items[] = $decoded;
            }
        }

        return $items;
    }

    /**
     * Retorna todos os itens da fila como objetos.
     *
     * @param string $file Caminho do arquivo NDJSON.
     * @return array Retorna um array de objetos stdClass.
     */
    public function getAsObjects(string $file): array
    {
        if (!file_exists($file)) {
            return [];
        }

        if (!str_contains($file, '.ndjson')) {
            $file = str_replace('.json', '.ndjson', $file);
        }
        $file = $this->queueDir . $file;
        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $items = [];

        foreach ($lines as $line) {
            $decoded = json_decode($line); // como objeto
            if (is_object($decoded)) {
                $items[] = $decoded;
            }
        }

        return $items;
    }

    /**
     * Remove e retorna o primeiro item da fila NDJSON.
     *
     * @param string $file Caminho do arquivo NDJSON.
     * @return array|null Retorna o item removido como array, ou null se fila vazia ou erro.
     */ /**
     * Remove e retorna o primeiro item da fila NDJSON,
     * lendo no máximo $limit linhas.
     *
     * @param string $file Caminho do arquivo NDJSON.
     * @param int|null $limit Limite de linhas a ler. Null para ler tudo.
     * @return array|null Item removido ou null se vazio/erro.
     */
    public function dequeue(string $file, ?int $limit = null): ?array
    {
        if (!file_exists($file)) {
            return null;
        }

        if (!str_contains($file, '.ndjson')) {
            $file = str_replace('.json', '.ndjson', $file);
        }

        $lockFile = $file . '.lock';
        if (!$this->acquireLock($lockFile)) {
            return null;
        }

        $file = $this->queueDir . $file;

        try {
            $allLines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

            if (empty($allLines)) {
                return null;
            }

            // Se limite for nulo ou maior que total, usa total
            $limit = ($limit === null || $limit > count($allLines)) ? count($allLines) : $limit;

            $linesToProcess = array_slice($allLines, 0, $limit);
            $linesLeftover = array_slice($allLines, $limit);

            $itemRemoved = null;
            $newLinesToProcess = [];

            foreach ($linesToProcess as $line) {
                if ($itemRemoved === null) {
                    $decoded = json_decode($line, true);
                    if (is_array($decoded)) {
                        $itemRemoved = $decoded; // remove o primeiro válido
                        // não adiciona essa linha à nova lista (removida)
                        continue;
                    }
                }
                // mantém a linha
                $newLinesToProcess[] = $line;
            }

            // Reescreve o arquivo com as linhas não removidas + as linhas não processadas
            $newFileLines = array_merge($newLinesToProcess, $linesLeftover);
            file_put_contents($file, implode(PHP_EOL, $newFileLines) . PHP_EOL, LOCK_EX);

            return $itemRemoved;
        } catch (\Throwable $e) {
            return null;
        } finally {
            $this->releaseLock($lockFile);
        }
    }


    /**
     * Tenta adquirir o lock para um determinado arquivo.
     *
     * @param string $lockFile Caminho do arquivo .lock.
     * @return bool True se lock adquirido, False se já estiver em uso.
     */
    private function acquireLock(string $lockFile): bool
    {
        if (file_exists($lockFile)) {
            return false; // Já está em execução
        }

        return @file_put_contents($lockFile, getmypid()) !== false;
    }

    /**
     * Remove o lock de um determinado arquivo.
     *
     * @param string $lockFile Caminho do arquivo .lock.
     * @return void
     */
    private function releaseLock(string $lockFile): void
    {
        if (file_exists($lockFile)) {
            @unlink($lockFile);
        }
    }
}
