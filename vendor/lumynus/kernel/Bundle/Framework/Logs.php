<?php

declare(strict_types=1);

namespace Lumynus\Bundle\Framework;

use Lumynus\Bundle\Framework\Config;
use Lumynus\Bundle\Framework\LumaClasses;

final class Logs extends LumaClasses
{
    /**
     * Caminho para o diretório de logs.
     */
    private static function path(): string
    {
        $path = Config::pathProject() .
            DIRECTORY_SEPARATOR . 'storage' .
            DIRECTORY_SEPARATOR . 'logs' .
            DIRECTORY_SEPARATOR;

        if (!is_dir($path)) {
            if (!mkdir($path, 0755, true)) {
                throw new \RuntimeException("Falha ao criar diretório de logs: {$path}");
            }
        }

        // Normaliza caminho absoluto
        $realPath = realpath($path);
        if ($realPath === false) {
            throw new \RuntimeException("Falha ao resolver caminho absoluto do diretório de logs.");
        }

        return rtrim($realPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    }

    /**
     * Salva os erros em formato JSON
     */
    public static function register(string $who, mixed $error): void
    {
        date_default_timezone_set("America/Sao_Paulo");
        $dir = self::path();
        $fileName = self::sanitizeFileName(date("d-m-y")) . ".json";
        $filePath = $dir . $fileName;

        $logs = file_exists($filePath)
            ? json_decode(file_get_contents($filePath), true)
            : [];

        $logs[] = [
            "who" => $who,
            "timestamp" => date("c"),
            "error" => $error
        ];

        $jsonPretty = json_encode($logs, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        file_put_contents($filePath, $jsonPretty, LOCK_EX);
    }

    /**
     * Remove todos os arquivos da pasta de logs caso haja 30 ou mais arquivos
     */
    public static function clear(): void
    {
        $dir = self::path();
        $arquivos = array_diff(scandir($dir), ['.', '..']);

        if (count($arquivos) >= 30) {
            foreach ($arquivos as $arquivo) {
                $filePath = $dir . $arquivo;

                // Proteção: garante que o arquivo está dentro do diretório de logs
                if (is_file($filePath) && str_starts_with(realpath($filePath), $dir)) {
                    unlink($filePath);
                }
            }
        }
    }

    /**
     * Sanitiza nomes de arquivos para evitar path traversal
     */
    private static function sanitizeFileName(string $name): string
    {
        $filename = basename($name); // remove caminhos
        $filename = preg_replace('/[^a-zA-Z0-9_\-]/', '', $filename);

        if (empty($filename)) {
            throw new \InvalidArgumentException("Nome de arquivo inválido fornecido.");
        }

        return $filename;
    }
}
