<?php
declare(strict_types=1);

namespace Lumynus\Bundle\Framework;

use RuntimeException;
use Lumynus\Bundle\Framework\Config;
use Lumynus\Bundle\Framework\LumaClasses;

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
     * Lê um arquivo .luma e retorna o valor original
     */
    public function read(string $filename): mixed
    {
        $path = $this->getPath($filename);
        if (!file_exists($path)) return null;

        $content = file_get_contents($path);
        return @unserialize($content); // @ para evitar warnings se corrompido
    }

    /**
     * Salva um valor PHP em arquivo .luma
     * $overwrite = true -> sobrescreve
     * $overwrite = false -> cria nome único tipo nome_1.luma
     */
    public function write(string $filename, mixed $value, bool $overwrite = true): string|false
    {
        $path = $overwrite ? $this->getPath($filename) : $this->getUniquePath($filename);
        return file_put_contents($path, serialize($value)) !== false ? basename($path) : false;
    }

    /**
     * Deleta um arquivo .luma
     */
    public function delete(string $filename): bool
    {
        $path = $this->getPath($filename);
        return file_exists($path) ? unlink($path) : false;
    }

    /**
     * Lista todos os arquivos .luma da pasta
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
