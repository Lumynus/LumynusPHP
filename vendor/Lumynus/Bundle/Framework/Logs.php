<?php

declare(strict_types=1);

namespace Lumynus\Bundle\Framework;

use Lumynus\Bundle\Framework\Config;
use Lumynus\Bundle\Framework\LumaClasses;

class Logs extends LumaClasses
{

    /**
     * Caminho para o diretório de logs.
     * Pode ser configurado conforme necessário.
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
        return $path;
    }


    /**
     * Função que salva os erros em formato json
     * @access public
     * @param string $who - Informação de quem envio o erro
     * @param mixed $error - Erro declarado
     * @return void
     */
    public static function register(string $who, mixed $error): void
    {
        date_default_timezone_set("America/Sao_Paulo");
        $file = self::path() . date("d-m-y") . ".json";
        $logs = file_exists($file)
            ? json_decode(file_get_contents($file), true)
            : [];
        $logs[] = [
            "who" => $who,
            "timestamp" => date("c"),
            "error" => $error
        ];
        $jsonPretty = json_encode($logs, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        file_put_contents($file, $jsonPretty, LOCK_EX);
    }


    /**
     * Remove todos os arquivos da pasta de logs caso haja 30 ou mais arquivos.
     * 
     * @return void
     */
    public static function limpaLogs()
    {
        $caminho = self::path();
        $arquivos = array_diff(scandir($caminho), ['.', '..']);
        if (count($arquivos) >= 30) {
            foreach ($arquivos as $arquivo) {
                $caminhoArquivo = $caminho . $arquivo;

                if (is_file($caminhoArquivo)) {
                    unlink($caminhoArquivo);
                }
            }
        }
    }
}
