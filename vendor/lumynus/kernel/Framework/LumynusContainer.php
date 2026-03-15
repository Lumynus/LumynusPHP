<?php

declare(strict_types=1);

/**
 * @author Weleny Santos <welenysantos@gmail.com>
 * @package Lumynus\Framework
 */

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
     * Armazena o trace de chamadas.
     * 
     * @var array<int, array>
     */
    private static array $traceTree = [];

    /**
     * Pilha de classes sendo resolvidas.
     * @var array<int, string>
     */
    private static array $stack = [];

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
        $startTime = microtime(true);
        $startMemory = memory_get_usage();

        $depth = count(self::$stack);
        self::$stack[] = $class;

        $key ??= $class;
        $isPersistent = Config::getApplicationConfig()['persistentRuntime']['is'] ?? false;

        if ($isPersistent) {
            $instance = new $class(...$options);
        } else {
            if (isset(self::$instances[$key])) {
                $instance = self::$instances[$key];
            } else {
                self::$instances[$key] = new $class(...$options);
                $instance = self::$instances[$key];
            }
        }

        $endTime = microtime(true);
        $endMemory = memory_get_usage();

        array_pop(self::$stack);

        if ($class !== ContainerProxy::class) {

            self::$traceTree[] = [
                'class' => $class,
                'depth' => $depth,
                'time' => ($endTime - $startTime) * 1000,
                'memory' => $endMemory - $startMemory
            ];
        }

        return $instance;
    }

    /**
     * Limpa completamente o container de forma manual, caso necessário.
     */
    public static function clear(): void
    {
        $isPersistent = Config::getApplicationConfig()['persistentRuntime']['is'] ?? false;
        if (!$isPersistent) {
            return;
        }
        self::$instances = [];
        self::$traceTree = [];
        self::$stack = [];
    }

    /**
     * Método para obter o trace de chamadas.
     * @return string Retorna o trace de chamadas.
     */
    public static function getTrace(): string
    {
        $output = "Lumynus Container Trace\n\n";

        $totalTime = 0;
        $totalMemory = 0;
        $totalClasses = count(self::$traceTree);

        foreach (self::$traceTree as $node) {
            $totalTime += $node['time'];
            $totalMemory += $node['memory'];
        }

        $output .= "Classes resolved: {$totalClasses}\n";
        $output .= "Total time: " . number_format($totalTime, 2) . " ms\n";
        $output .= "Total memory: " . number_format($totalMemory / 1024, 2) . " KB\n\n";

        foreach (self::$traceTree as $node) {

            $depth = $node['depth'];

            $indent = str_repeat("│   ", $depth);
            $branch = $depth === 0 ? "" : "├── ";

            $time = number_format($node['time'], 2);
            $memory = number_format($node['memory'] / 1024, 2);

            $color = 'green';

            if ($node['time'] > 5) {
                $color = 'red';
            } elseif ($node['time'] > 1) {
                $color = 'yellow';
            }

            $warning = $node['time'] > 10 ? " ⚠ Class too heavy" : "";

            $class = self::color($node['class'], $color);

            $output .= "{$indent}{$branch}{$class} ({$time} ms | {$memory} KB){$warning}\n";
        }

        if (php_sapi_name() !== 'cli') {
            return "<pre>{$output}</pre>";
        }

        return $output;
    }

    /**
     * Método para colorir o texto.
     * @param string $text Texto a ser colorido.
     * @param string $color Cor a ser aplicada.
     * @return string Texto colorido.
     */
    private static function color(string $text, string $color): string
    {
        if (php_sapi_name() === 'cli') {

            $colors = [
                'green' => "\033[32m",
                'yellow' => "\033[33m",
                'red' => "\033[31m",
                'cyan' => "\033[36m",
                'reset' => "\033[0m"
            ];

            return $colors[$color] . $text . $colors['reset'];
        }

        return "<span style='color:{$color}'>{$text}</span>";
    }

    private function __construct() {}
}
