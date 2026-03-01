<?php

declare(strict_types=1);

namespace Lumynus\Framework;

use Lumynus\Framework\Config;
use Lumynus\Framework\LumaClasses;

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
     * Registra uma entrada de log em arquivo JSON diário.
     *
     * O log é salvo em storage/logs/DD-MM-YY.json e a escrita é protegida
     * contra concorrência usando flock.
     *
     * @param string $who   Origem ou contexto do log.
     * @param mixed  $error Dado do erro (string, array, objeto ou exceção).
     * @return void
     */
    public static function register(string $who, mixed $error): void
    {
        $dir = self::path();
        $fileName = self::sanitizeFileName(date("d-m-y")) . ".json";
        $filePath = $dir . $fileName;

        $fp = fopen($filePath, 'c+');
        if (!$fp) {
            return;
        }

        if (!flock($fp, LOCK_EX)) {
            fclose($fp);
            return;
        }

        try {
            rewind($fp);
            $content = stream_get_contents($fp);

            $logs = [];
            if ($content !== false && trim($content) !== '') {
                $decoded = json_decode($content, true);
                if (is_array($decoded)) {
                    $logs = $decoded;
                }
            }

            $logs[] = [
                "who" => $who,
                "timestamp" => date("c"),
                "error" => $error
            ];

            ftruncate($fp, 0);
            rewind($fp);

            fwrite(
                $fp,
                json_encode(
                    $logs,
                    JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                )
            );
            fflush($fp);
        } finally {
            flock($fp, LOCK_UN);
            fclose($fp);
        }
    }


    /**
     * Remove todos os arquivos da pasta de logs caso haja 30 ou mais arquivos
     */
    public static function clear(): void
    {
        $dir = self::path();
        $files = array_diff(scandir($dir), ['.', '..']);

        if (self::l_countStatic($files) < 30) {
            return;
        }

        sort($files);

        $toDelete = array_slice($files, 0, self::l_countStatic($files) - 29);

        foreach ($toDelete as $file) {
            $filePath = $dir . $file;

            if (!is_file($filePath)) {
                continue;
            }

            if (str_contains($file, date('d-m-y'))) {
                continue;
            }

            $fp = @fopen($filePath, 'r');
            if ($fp === false) {
                continue;
            }

            if (!flock($fp, LOCK_EX | LOCK_NB)) {
                fclose($fp);
                continue;
            }

            fclose($fp);
            @unlink($filePath);
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
