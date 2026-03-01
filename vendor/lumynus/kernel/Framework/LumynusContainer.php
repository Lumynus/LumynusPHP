<?php

declare(strict_types=1);

namespace Lumynus\Framework;

use Lumynus\Framework\Config;

final class LumynusContainer
{
    /**
     * Armazena as instâncias criadas.
     * @var array<string, object>
     */
    private static array $instances = [];

    /**
     * Resolve e retorna uma instância da classe solicitada.
     *
     * @param string $class O namespace da classe a ser instanciada.
     * @param array $options Argumentos a serem passados para o construtor.
     * @param string|null $key Chave de identificação única no container.
     * @return object Retorna a instância da classe.
     */
    public static function resolve(string $class, array $options = [], ?string $key = null): object
    {
        $key ??= $class;
        $isPersistent = Config::getApplicationConfig()['persistentRuntime']['is'] ?? false;

        if ($isPersistent) {
            return new $class(...$options);
        }

        if (isset(self::$instances[$key])) {
            return self::$instances[$key];
        }

        self::$instances[$key] = new $class(...$options);
        return self::$instances[$key];
    }

    /**
     * Limpa completamente o container de forma manual, caso necessário.
     */
    public static function clear(): void
    {
        $isPersistent = Config::getApplicationConfig()['persistentRuntime']['is'] ?? false;
        if(!$isPersistent) {
            return;
        }
        self::$instances = [];
    }

    private function __construct() {}
}