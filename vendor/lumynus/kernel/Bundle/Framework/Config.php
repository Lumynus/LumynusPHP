<?php

declare(strict_types=1);

namespace Lumynus\Bundle\Framework;

use Lumynus\Bundle\Framework\LumaClasses;

final class Config extends LumaClasses
{

    /**
     * Obtém as configurações do arquivo config.ini.
     *
     * @return array|null Retorna um array com as configurações ou null se o arquivo não existir.
     */
    public static function getINI(): ?array
    {
        $file = self::pathProject() . DIRECTORY_SEPARATOR . 'config.ini';
        if (!file_exists($file)) {
            return null;
        }
        $config =  parse_ini_file($file, true);
        return $config ?? null;
    }

    /**
     * Obtém as configurações do arquivo aplication.json.
     *
     * @return array|null Retorna um array com as configurações ou null se o arquivo não existir.
     */
    public static function getAplicationConfig(): ?array
    {
        $file = self::pathProject() . DIRECTORY_SEPARATOR . 'aplication.json';

        if (file_exists($file)) {
            $config = json_decode(file_get_contents($file), true);

            if (is_array($config) && isset($config[0])) {
                return $config[0];
            }
        }

        // Se não existir ou estiver inválido, retorna padrão
        return [
            "App" => [
                "nameApplication" => "Lumynus",
                "version" => "1",
                "description" => "A simple PHP framework for building web applications.",
                "author" => "Welen",
                "email" => "",
                "host" => "www.exemple.com"
            ],
            "path" => [
                "public" => "/public/",
                "js" => "/resources/js/",
                "css" => "/resources/css/",
                "views" => "/src/views/",
                "routers" => "/src/routers/",
                "cache" => "/storage/cache/"
            ],
            "security" => [
                "csrf" => [
                    "enabled" => true,
                    "nameToken" => "luma_csrf"
                ],
                "integrityAssets" => [
                    "enabled" => true
                ],
                "session" =>  [
                    "secret" => "2025_trx$#@@lum@nysCryptSessionsDates"
                ],
                "cookie" => [
                    "secret" => "2025_trx$#@@lum@nysCryptCookiesDates"
                ]
            ],
            "frontend" => [
                "versionAssets" => true
            ],
        ];
    }


    /**
     * Retorna o caminho do projeto Lumynus.
     *
     * @return string Caminho absoluto do diretório raiz do projeto.
     */
    public static function pathProject(): string
    {
        return dirname(__DIR__, 4);
    }

    /**
     * Obtém o valor de uma configuração específica.
     *
     * @param string $key Chave da configuração.
     * @param mixed $default Valor padrão a ser retornado se a chave não existir.
     * @return mixed Retorna o valor da configuração ou o valor padrão.
     */
    public static function modeProduction(): bool
    {
        $config = self::getINI();
        if (isset($config['app']['mode']) && !$config['app']['mode'] === 'development') {
            return true;
        }
        return false;
    }

    /**
     * Define o modo de produção ou desenvolvimento no arquivo config.ini.
     *
     * @param bool $isProduction Define se o modo é produção (true) ou desenvolvimento (false).
     * @return void
     */
    public static function setModeProduction(bool $isProduction): void
    {
        $file = self::pathProject() . DIRECTORY_SEPARATOR . 'config.ini';
        $config = self::getINI() ?? [];

        if (!isset($config['app'])) {
            $config['app'] = [];
        }

        $config['app']['mode'] = $isProduction ? 'production' : 'development';
        $config['app']['debug'] = $isProduction ? 'false' : 'true';

        $iniContent = '';
        foreach ($config as $section => $values) {
            $iniContent .= "[$section]\n";
            foreach ($values as $key => $value) {
                $iniContent .= "$key = $value\n";
            }
            $iniContent .= "\n";
        }

        file_put_contents($file, $iniContent);
    }

    /**
     * Método para obter a instância da classe Luma.
     * @return Luma Retorna uma nova instância da classe Luma.
     */
    public function __debugInfo(): array
    {
        return [
            'Lumynus' => "Framework PHP"
        ];
    }
}
