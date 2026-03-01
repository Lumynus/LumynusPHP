<?php

declare(strict_types=1);

namespace Lumynus\Framework;

use RuntimeException;
use Lumynus\Framework\Config;
use Lumynus\Framework\LumaClasses;

final class Memory extends LumaClasses
{
    private string $memoryDir;

    public function __construct()
    {
        $this->memoryDir = Config::pathProject()
            . DIRECTORY_SEPARATOR . 'storage'
            . DIRECTORY_SEPARATOR . 'memory'
            . DIRECTORY_SEPARATOR;

        if (!is_dir($this->memoryDir) && !mkdir($this->memoryDir, 0755, true)) {
            throw new RuntimeException("Failed to create memory directory: {$this->memoryDir}");
        }
    }

    /**
     * Lê um arquivo .luma e retorna o valor desserializado.
     *
     * @param string $filename Nome do arquivo (sem extensão ou com extensão)
     *
     * @return mixed|null Retorna o valor original ou null se o arquivo não existir
     */
    public function read(string $filename): mixed
    {
        $path = $this->getPath($filename);
        if (!file_exists($path)) {
            return null;
        }

        $content = file_get_contents($path);
        if ($content === false || $content === '') {
            return null;
        }

        $value = @unserialize($content, ['allowed_classes' => false]);

        if ($value === false && $content !== serialize(false)) {
            return null;
        }

        return $value;
    }

    /**
     * Serializa e salva um valor PHP em um arquivo .luma.
     *
     * @param string $filename Nome base do arquivo
     * @param mixed  $value Valor a ser serializado
     * @param bool   $overwrite Define se deve sobrescrever um arquivo existente
     *                           - true: sobrescreve
     *                           - false: gera um nome único (ex: nome_1.luma)
     *
     * @return string|false Nome do arquivo salvo em caso de sucesso ou false em caso de falha
     */
    public function write(string $filename, mixed $value, bool $overwrite = true): string|false
    {
        $finalPath = $overwrite
            ? $this->getPath($filename)
            : $this->getUniquePath($filename);

        $tmpPath = $finalPath . '.tmp';
        $data = serialize($value);

        if (file_put_contents($tmpPath, $data, LOCK_EX) === false) {
            return false;
        }

        if (!rename($tmpPath, $finalPath)) {
            @unlink($tmpPath);
            return false;
        }

        return basename($finalPath);
    }

    /**
     * Remove um arquivo .luma.
     *
     * @param string $filename Nome do arquivo
     *
     * @return bool True se o arquivo foi removido, false se não existir ou falhar
     */
    public function delete(string $filename): bool
    {
        $path = $this->getPath($filename);
        return file_exists($path) ? unlink($path) : false;
    }

    /**
     * Lista todos os arquivos .luma presentes no diretório de armazenamento.
     *
     * @return string[] Lista de nomes de arquivos .luma
     */
    public function list(): array
    {
        $files = array_diff(scandir($this->memoryDir), ['.', '..']);
        return array_filter($files, fn($f) => str_ends_with($f, '.luma'));
    }

    /**
     * Garante que o path final esteja dentro da pasta storage
     */
    private function getPath(string $filename): string
    {
        $filename = $this->sanitizeFilename($filename);
        return $this->memoryDir . $filename . '.luma';
    }

    /**
     * Gera caminho único se o arquivo já existir
     */
    private function getUniquePath(string $filename): string
    {
        $filename = $this->sanitizeFilename($filename);
        $path = $this->memoryDir . $filename . '.luma';
        $i = 1;

        while (file_exists($path)) {
            $path = $this->memoryDir . $filename . '_' . $i . '.luma';
            $i++;
        }

        return $path;
    }

    /**
     * Remove path traversal e caracteres inválidos
     */
    private function sanitizeFilename(string $filename): string
    {
        // Remove caminhos relativos e subpastas
        $filename = basename($filename);
        // Substitui caracteres inválidos por _
        return preg_replace('/[^A-Za-z0-9_\-]/', '_', $filename);
    }
}
