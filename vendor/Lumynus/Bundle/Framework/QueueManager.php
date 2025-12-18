<?php

declare(strict_types=1);

namespace Lumynus\Bundle\Framework;

use Lumynus\Bundle\Framework\LumaClasses;
use Lumynus\Bundle\Framework\Config;
use Lumynus\Bundle\Framework\Logs;

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
        // Remove diretórios e pega apenas o nome base
        $filename = basename($file);

        // Remove caracteres não permitidos (apenas letras, números, underscore e hífen)
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
            $this->log("Empty data provided", 'error');
            return false;
        }

        $filePath = $this->getFilePath($file);

        try {
            $jsonLine = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            if ($jsonLine === false) {
                $this->log("JSON encode failed: " . json_last_error_msg(), 'error');
                return false;
            }

            // Abre o arquivo em append (cria se não existir)
            $fp = fopen($filePath, 'a');
            if (!$fp) {
                $this->log("File open failed: {$filePath}", 'error');
                return false;
            }

            // Lock exclusivo (espera até liberar)
            if (flock($fp, LOCK_EX)) {
                $bytes = fwrite($fp, $jsonLine . PHP_EOL);
                fflush($fp); // garante flush no disco
                flock($fp, LOCK_UN);
            } else {
                $this->log("Could not acquire flock: {$filePath}", 'error');
                fclose($fp);
                return false;
            }

            fclose($fp);

            if ($bytes === false) {
                $this->log("File write failed: {$filePath}", 'error');
                return false;
            }

            $this->log("Insert successful, bytes written: {$bytes}");
            return true;
        } catch (\Throwable $e) {
            $this->log("Insert exception: " . $e->getMessage(), 'error');
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
            $this->log("File does not exist: {$filePath}", 'error');
            return false;
        }

        // Abre o arquivo para leitura e escrita
        $fp = fopen($filePath, 'c+'); // 'c+' cria se não existir e permite leitura/escrita
        if (!$fp) {
            $this->log("Failed to open file: {$filePath}", 'error');
            return false;
        }

        // Lock exclusivo (espera até liberar)
        if (!flock($fp, LOCK_EX)) {
            $this->log("Could not acquire flock: {$filePath}", 'error');
            fclose($fp);
            return false;
        }

        try {
            // Lê todas as linhas
            $lines = [];
            while (($line = fgets($fp)) !== false) {
                $line = trim($line);
                if ($line !== '') $lines[] = $line;
            }

            if (empty($lines)) return false;

            // Filtra as linhas que NÃO correspondem à chave/valor
            $newLines = [];
            foreach ($lines as $line) {
                $decoded = json_decode($line, true);
                if (!is_array($decoded) || !isset($decoded[$key]) || $decoded[$key] !== $value) {
                    $newLines[] = $line;
                }
            }

            // Sobrescreve o arquivo com segurança
            ftruncate($fp, 0);
            rewind($fp);
            if (!empty($newLines)) {
                fwrite($fp, implode(PHP_EOL, $newLines) . PHP_EOL);
            }
        } catch (\Throwable $e) {
            $this->log("Remove exception: " . $e->getMessage(), 'error');
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

        // Abre o arquivo para leitura e escrita
        $fp = fopen($filePath, 'c+'); // 'c+' cria se não existir e permite leitura/escrita
        if (!$fp) {
            $this->log("Failed to open file: {$filePath}", 'error');
            return false;
        }

        // Lock exclusivo (espera até liberar)
        if (!flock($fp, LOCK_EX)) {
            $this->log("Could not acquire flock: {$filePath}", 'error');
            fclose($fp);
            return false;
        }

        try {
            // Lê todas as linhas
            $lines = [];
            while (($line = fgets($fp)) !== false) {
                $line = trim($line);
                if ($line !== '') $lines[] = $line;
            }

            if (empty($lines)) return false;

            // Atualiza as linhas que correspondem à chave/valor
            $updatedLines = [];
            foreach ($lines as $line) {
                $decoded = json_decode($line, true);
                if (is_array($decoded) && isset($decoded[$key]) && $decoded[$key] === $value) {
                    $decoded = array_merge($decoded, $newData);
                    $line = json_encode($decoded, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                }
                $updatedLines[] = $line;
            }

            // Sobrescreve o arquivo com segurança
            ftruncate($fp, 0);
            rewind($fp);
            fwrite($fp, implode(PHP_EOL, $updatedLines) . PHP_EOL);
        } catch (\Throwable $e) {
            $this->log("Update exception: " . $e->getMessage(), 'error');
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

        // Remove o arquivo de lock se existir
        if (file_exists($lockFile)) {
            if (!@unlink($lockFile)) {
                $this->log("Failed to delete lock file: {$lockFile}", 'error');
                $success = false;
            } else {
                $this->log("Lock file deleted: {$lockFile}");
            }
        }

        // Remove o arquivo da fila se existir
        if (file_exists($filePath)) {
            if (!@unlink($filePath)) {
                $this->log("Failed to delete queue file: {$filePath}", 'error');
                return false;
            } else {
                $this->log("Queue file deleted: {$filePath}");
            }
        } else {
            $this->log("Queue file does not exist: {$filePath}", 'error');
            return false;
        }

        return $success;
    }

    /**
     * Retorna todos os itens da fila como array associativo.
     * 
     * @param string $file Nome do arquivo NDJSON
     * @return array Array de arrays
     */
    public function getAsArray(string $file): array
    {
        $filePath = $this->getFilePath($file);

        if (!file_exists($filePath)) return [];

        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) return [];

        $items = [];
        foreach ($lines as $line) {
            $decoded = json_decode($line, true);
            if (is_array($decoded)) $items[] = $decoded;
        }

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

        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) return [];

        $items = [];
        foreach ($lines as $line) {
            $decoded = json_decode($line);
            if (is_object($decoded)) $items[] = $decoded;
        }

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

        // Abre o arquivo para leitura e escrita
        $fp = fopen($filePath, 'c+'); // 'c+' cria se não existir e permite leitura/escrita
        if (!$fp) {
            $this->log("Failed to open file: {$filePath}", 'error');
            return null;
        }

        // Lock exclusivo (espera até liberar)
        if (!flock($fp, LOCK_EX)) {
            $this->log("Could not acquire flock: {$filePath}", 'error');
            fclose($fp);
            return null;
        }

        try {
            // Lê todas as linhas
            $lines = [];
            while (($line = fgets($fp)) !== false) {
                $line = trim($line);
                if ($line !== '') $lines[] = $line;
            }

            if (empty($lines)) return null;

            // Determina o limite
            $limit = ($limit === null || $limit > count($lines)) ? count($lines) : $limit;

            $linesToProcess = array_slice($lines, 0, $limit);
            $linesLeftover = array_slice($lines, $limit);

            $itemsRemoved = [];
            foreach ($linesToProcess as $line) {
                $decoded = json_decode($line, true);
                if (is_array($decoded)) $itemsRemoved[] = $decoded;
            }

            // Escreve apenas as linhas que sobraram (após processar)
            ftruncate($fp, 0);       // limpa arquivo
            rewind($fp);             // volta pro início
            if (!empty($linesLeftover)) {
                fwrite($fp, implode(PHP_EOL, $linesLeftover) . PHP_EOL);
            }
        } catch (\Throwable $e) {
            $this->log("Dequeue exception: " . $e->getMessage(), 'error');
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
        Logs::register($message, 'QueueManager');
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
