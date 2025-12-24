<?php

declare(strict_types=1);

namespace Lumynus\Bundle\Framework;

use ReflectionClass;
use RuntimeException;
use Lumynus\Bundle\Framework\LumynusCommands;

final class CommandDispatcher
{

    private const INTERNAL_TOKEN = '__LUMYNUS_KERNEL__byWelenySantos';

    private function __construct(string $token)
    {
        if ($token !== self::INTERNAL_TOKEN) {
            throw new \RuntimeException(
                'CommandDispatcher can only be instantiated by the framework.'
            );
        }
    }

    /**
     * Inicia o dispatcher e executa o comando informado.
     *
     * @param array<int, string> $argv
     */
    public static function boot(array $argv): void
    {
        $self = new self(self::INTERNAL_TOKEN);
        $self->dispatch($argv);
    }

    /**
     * @param array<int, string> $argv
     */
    public function dispatch(array $argv): void
    {
        $argv = array_values($argv);

        $classInput = $argv[0] ?? null;

        if (!$classInput) {
            throw new RuntimeException('No command class informed.');
        }

        $methodInput = $argv[1] ?? null;
        if($methodInput !== null && str_starts_with($methodInput, '--')) {
            $methodInput = str_replace('--', '', $methodInput);
        }

        $params = array_slice($argv, 2);

        $class = $this->resolveCommandClass($classInput);

        if (!class_exists($class)) {
            throw new RuntimeException("Command {$class} not found.");
        }

        $ref = new ReflectionClass($class);

        if (!$ref->isSubclassOf(LumynusCommands::class)) {
            throw new RuntimeException("{$class} is not a valid Command.");
        }

        $instance = $ref->newInstance();

        // Se método NÃO foi informado → chama handle()
        if ($methodInput === null) {
            if (!$ref->hasMethod('handle')) {
                throw new RuntimeException(
                    "{$class} must implement handle()."
                );
            }

            $ref->getMethod('handle')->invoke($instance, $params);
            return;
        }

        // Método informado
        if (!$ref->hasMethod($methodInput)) {
            throw new RuntimeException(
                "Method {$methodInput} not found in {$class}."
            );
        }

        $method = $ref->getMethod($methodInput);

        if (!$method->isPublic()) {
            throw new RuntimeException(
                "Method {$methodInput} must be public."
            );
        }

        $method->invoke($instance, $params);
    }


    /**
     * Resolve o nome da classe do comando a partir do input.
     */
    private function resolveCommandClass(string $input): string
    {
        /**
         * Exemplos:
         *  oi                 → OiCommand
         *  oi-command         → OiCommand
         *  usuario_command    → UsuarioCommand
         *  UsuarioCommand     → UsuarioCommand
         */

        // Normaliza separadores
        $normalized = str_replace([':', '-', '_'], ' ', $input);

        // Converte para StudlyCase
        $class = str_replace(' ', '', ucwords($normalized));

        // Adiciona sufixo apenas se não existir
        if (!str_ends_with($class, 'Command')) {
            $class .= 'Command';
        }

        return 'App\\Commands\\' . $class;
    }
}
