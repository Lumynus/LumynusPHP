<?php

declare(strict_types=1);

/**
 * @author Weleny Santos <welenysantos@gmail.com>
 * @package Lumynus\Framework
 */

namespace Lumynus\Framework;

use Lumynus\Framework\LumaClasses;
use Lumynus\Framework\Config;
use Lumynus\Framework\Logs;

/**
 * Class QueueManager
 *
 * Manages NDJSON queues with support for insert, update, remove, dequeue and read operations.
 * Each queue is stored in NDJSON files in the specified directory.
 * Has locking mechanism to avoid concurrency issues.
 */
final class QueueManager extends LumaClasses
{
    private string $queueDir = '';


    /**
     * Construtor. Inicializa o diretório de filas baseado na configuração do projeto.
     * 
     * @param bool $debug Habilita logs de debug
     * @throws \RuntimeException Se o diretório de filas não puder ser criado
     */
    public function __construct()
    {

        $this->queueDir = Config::pathProject()
            . DIRECTORY_SEPARATOR . 'storage'
            . DIRECTORY_SEPARATOR . 'queues'
            . DIRECTORY_SEPARATOR;

        if (!is_dir($this->queueDir) && !mkdir($this->queueDir, 0755, true)) {
            throw new \RuntimeException("Failed to create queue directory: {$this->queueDir}");
        }
    }

    /**
     * Retorna o caminho completo do arquivo NDJSON.
     * 
     * @param string $file Nome do arquivo
     * @return string Caminho completo do arquivo NDJSON
     */
    private function getFilePath(string $file): string
    {
        $filename = basename($file);
        $filename = preg_replace('/[^a-zA-Z0-9_\-]/', '', $filename);

        if (empty($filename)) {
            throw new \InvalidArgumentException("Invalid queue file name.");
        }

        return $this->queueDir . $filename . '.ndjson';
    }


    /**
     * Retorna o caminho completo do arquivo de lock correspondente.
     * 
     * @param string $file Caminho do arquivo NDJSON
     * @return string Caminho do arquivo de lock
     */
    private function getLockPath(string $file): string
    {
        return $file . '.lock';
    }

    /**
     * Insere um novo item no final da fila NDJSON.
     * 
     * @param array $data Item a ser inserido (array associativo)
     * @param string $file Nome do arquivo da fila
     * @return bool True se sucesso, False se erro ou fila bloqueada
     */
    public function insert(array $data, string $file): bool
    {
        if (empty($data)) {
            $this->log("Empty data provided");
            return false;
        }

        $filePath = $this->getFilePath($file);

        try {
            $jsonLine = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            if ($jsonLine === false) {
                $this->log("JSON encode failed: " . json_last_error_msg());
                return false;
            }

            $fp = fopen($filePath, 'c+');
            if (!$fp) {
                $this->log("File open failed: {$filePath}");
                return false;
            }

            if (flock($fp, LOCK_EX)) {
                $bytes = fwrite($fp, $jsonLine . PHP_EOL);
                fflush($fp);
                flock($fp, LOCK_UN);
            } else {
                $this->log("Could not acquire flock: {$filePath}");
                fclose($fp);
                return false;
            }

            fclose($fp);

            if ($bytes === false) {
                $this->log("File write failed: {$filePath}");
                return false;
            }

            $this->log("Insert successful, bytes written: {$bytes}");
            return true;
        } catch (\Throwable $e) {
            $this->log("Insert exception: " . $e->getMessage());
            return false;
        }
    }


    /**
     * Remove linhas da fila que contenham uma chave com determinado valor.
     * 
     * @param string $file Nome do arquivo NDJSON
     * @param string $key Chave a ser verificada
     * @param mixed $value Valor correspondente para remoção
     * @return bool True se sucesso, False se erro ou fila bloqueada
     */
    public function remove(string $file, string $key, $value): bool
    {
        $filePath = $this->getFilePath($file);

        if (!file_exists($filePath)) {
            $this->log("File does not exist: {$filePath}");
            return false;
        }

        $fp = fopen($filePath, 'c+');
        if (!$fp) {
            $this->log("Failed to open file: {$filePath}");
            return false;
        }

        if (!flock($fp, LOCK_EX)) {
            $this->log("Could not acquire flock: {$filePath}");
            fclose($fp);
            return false;
        }

        try {
            $lines = [];
            while (($line = fgets($fp)) !== false) {
                $line = trim($line);
                if ($line !== '') $lines[] = $line;
            }

            if (empty($lines)) return false;

            $newLines = [];
            foreach ($lines as $line) {
                $decoded = json_decode($line, true);
                if (json_last_error() !== JSON_ERROR_NONE) continue;
                if (!is_array($decoded) || !isset($decoded[$key]) || $decoded[$key] !== $value) {
                    $newLines[] = $line;
                }
            }

            ftruncate($fp, 0);
            rewind($fp);
            if (!empty($newLines)) {
                fwrite($fp, implode(PHP_EOL, $newLines) . PHP_EOL);
            }
        } catch (\Throwable $e) {
            $this->log("Remove exception: " . $e->getMessage());
            return false;
        } finally {
            flock($fp, LOCK_UN);
            fclose($fp);
        }

        return true;
    }


    /**
     * Atualiza linhas com base em uma chave e valor, mesclando com novos dados.
     * 
     * @param string $file Nome do arquivo NDJSON
     * @param string $key Chave a ser verificada
     * @param mixed $value Valor da chave
     * @param array $newData Novos dados para mesclar
     * @return bool True se sucesso, False se erro ou fila bloqueada
     */
    public function update(string $file, string $key, $value, array $newData): bool
    {
        $filePath = $this->getFilePath($file);

        if (!file_exists($filePath)) return false;

        $fp = fopen($filePath, 'c+');
        if (!$fp) {
            $this->log("Failed to open file: {$filePath}");
            return false;
        }

        if (!flock($fp, LOCK_EX)) {
            $this->log("Could not acquire flock: {$filePath}");
            fclose($fp);
            return false;
        }

        try {

            $lines = [];
            while (($line = fgets($fp)) !== false) {
                $line = trim($line);
                if ($line !== '') $lines[] = $line;
            }

            if (empty($lines)) return false;

            $updatedLines = [];
            foreach ($lines as $line) {
                $decoded = json_decode($line, true);
                if (is_array($decoded) && isset($decoded[$key]) && $decoded[$key] === $value) {
                    $decoded = array_merge($decoded, $newData);
                    $line = json_encode($decoded, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                    if (json_last_error() !== JSON_ERROR_NONE) continue;
                }
                $updatedLines[] = $line;
            }

            ftruncate($fp, 0);
            rewind($fp);
            fwrite($fp, implode(PHP_EOL, $updatedLines) . PHP_EOL);
        } catch (\Throwable $e) {
            $this->log("Update exception: " . $e->getMessage());
            return false;
        } finally {
            flock($fp, LOCK_UN);
            fclose($fp);
        }

        return true;
    }


    /**
     * Deleta um arquivo de fila NDJSON e seu lock correspondente.
     * 
     * @param string $file Nome do arquivo da fila
     * @return bool True se sucesso, False se erro
     */
    public function delete(string $file): bool
    {
        $filePath = $this->getFilePath($file);
        $lockFile = $this->getLockPath($filePath);

        $success = true;

        if (file_exists($lockFile)) {
            if (!@unlink($lockFile)) {
                $this->log("Failed to delete lock file: {$lockFile}");
                $success = false;
            } else {
                $this->log("Lock file deleted: {$lockFile}");
            }
        }

        if (file_exists($filePath)) {
            if (!@unlink($filePath)) {
                $this->log("Failed to delete queue file: {$filePath}");
                return false;
            } else {
                $this->log("Queue file deleted: {$filePath}");
            }
        } else {
            $this->log("Queue file does not exist: {$filePath}");
            return false;
        }

        return $success;
    }

    /**
     * Retorna todos os itens da fila como array associativo com segurança de Lock.
     * * @param string $file Nome do arquivo NDJSON
     * @return array Array de arrays
     */
    public function getAsArray(string $file): array
    {
        $filePath = $this->getFilePath($file);

        if (!file_exists($filePath)) return [];

        $fp = fopen($filePath, 'r');

        if (!$fp) {
            $this->log("Failed to open file for reading: {$filePath}");
            return [];
        }

        $items = [];

        if (flock($fp, LOCK_SH)) {

            $content = stream_get_contents($fp);
            flock($fp, LOCK_UN);

            if ($content) {
                $lines = explode(PHP_EOL, $content);
                foreach ($lines as $line) {
                    $line = trim($line);
                    if ($line === '') continue;

                    $decoded = json_decode($line, true);
                    if (json_last_error() !== JSON_ERROR_NONE) continue;
                    if (is_array($decoded)) {
                        $items[] = $decoded;
                    }
                }
            }
        } else {
            $this->log("Could not acquire shared lock for reading: {$filePath}");
        }
        fclose($fp);
        return $items;
    }

    /**
     * Retorna todos os itens da fila como objetos stdClass.
     * 
     * @param string $file Nome do arquivo NDJSON
     * @return array Array de objetos
     */
    public function getAsObjects(string $file): array
    {
        $filePath = $this->getFilePath($file);

        if (!file_exists($filePath)) return [];

        $fp = fopen($filePath, 'r');

        if (!$fp) {
            $this->log("Failed to open file for reading: {$filePath}");
            return [];
        }

        $items = [];

        if (flock($fp, LOCK_SH)) {

            $content = stream_get_contents($fp);
            flock($fp, LOCK_UN);

            if ($content) {
                $lines = explode(PHP_EOL, $content);

                foreach ($lines as $line) {
                    $line = trim($line);
                    if ($line === '') continue;

                    $decoded = json_decode($line);
                    if (json_last_error() !== JSON_ERROR_NONE) continue;
                    if (is_object($decoded)) {
                        $items[] = $decoded;
                    }
                }
            }
        } else {
            $this->log("Could not acquire shared lock for reading: {$filePath}");
        }
        fclose($fp);
        return $items;
    }

    /**
     * Remove e retorna itens da fila
     * 
     * @param string $file Nome do arquivo da fila
     * @param int|null $limit Número máximo de itens a remover (null = todos)
     * @return array|null Array com os itens removidos ou null se erro/vazio
     */
    public function dequeue(string $file, ?int $limit = null): ?array
    {
        $filePath = $this->getFilePath($file);

        if (!file_exists($filePath)) return null;

        $itemsRemoved = null;


        $fp = fopen($filePath, 'c+');
        if (!$fp) {
            $this->log("Failed to open file: {$filePath}");
            return null;
        }


        if (!flock($fp, LOCK_EX)) {
            $this->log("Could not acquire flock: {$filePath}");
            fclose($fp);
            return null;
        }

        try {

            $lines = [];
            while (($line = fgets($fp)) !== false) {
                $line = trim($line);
                if ($line !== '') $lines[] = $line;
            }

            if (empty($lines)) return null;

            $limit = ($limit === null || $limit > $this->l_count($lines)) ? $this->l_count($lines) : $limit;

            $linesToProcess = array_slice($lines, 0, $limit);
            $linesLeftover = array_slice($lines, $limit);

            $itemsRemoved = [];
            foreach ($linesToProcess as $line) {
                $decoded = json_decode($line, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    continue;
                }
                if (is_array($decoded)) $itemsRemoved[] = $decoded;
            }

            ftruncate($fp, 0);
            rewind($fp);
            if (!empty($linesLeftover)) {
                fwrite($fp, implode(PHP_EOL, $linesLeftover) . PHP_EOL);
            }
        } catch (\Throwable $e) {
            $this->log("Dequeue exception: " . $e->getMessage());
            return null;
        } finally {
            flock($fp, LOCK_UN);
            fclose($fp);
        }

        return empty($itemsRemoved) ? null : $itemsRemoved;
    }


    /**
     * Remove e retorna apenas UM item da fila (comportamento clássico de fila)
     * 
     * @param string $file Nome do arquivo da fila
     * @return array|null Item removido ou null se erro/vazio
     */
    public function dequeueOne(string $file): ?array
    {
        $result = $this->dequeue($file, 1);
        return $result ? $result[0] : null;
    }

    /**
     * Registra mensagens para debug e rastreamento de erros.
     * 
     * @param string $message Mensagem para registrar
     * @param string $level Nível do log (debug, error)
     * @return void
     */
    private function log(string $message): void
    {
        Logs::register('QueueManager', $message);
    }

    /**
     * Retorna informações sobre o diretório de filas.
     * 
     * @return array Array contendo informações do diretório, existência, capacidade de escrita e lista de arquivos
     */
    public function getQueueInfo(): array
    {
        return [
            'queueDir' => $this->queueDir,
            'exists' => is_dir($this->queueDir),
            'writable' => is_writable($this->queueDir),
            'files' => is_dir($this->queueDir) ? glob($this->queueDir . '*.ndjson') : []
        ];
    }
}
