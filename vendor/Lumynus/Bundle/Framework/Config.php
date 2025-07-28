<?php

declare(strict_types=1);

namespace Lumynus\Bundle\Framework;
use Lumynus\Bundle\Framework\LumaClasses;

class Config extends LumaClasses
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
     * Método para obter a instância da classe Luma.
     * @return Luma Retorna uma nova instância da classe Luma.
     */
    public function __debugInfo():array
    {
        return [
            'Lumynus' => "Framework PHP"
        ];
    }
}
