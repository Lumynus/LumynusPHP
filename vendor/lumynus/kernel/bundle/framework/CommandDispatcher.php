<?php

declare(strict_types=1);

namespace Lumynus\Bundle\Framework;

use ReflectionClass;
use ReflectionMethod;
use RuntimeException;
use Lumynus\Bundle\Framework\LumynusCommands;
use Lumynus\Console\ArgvTerminal;
use Lumynus\Console\Contracts\Terminal;
use Lumynus\Console\Contracts\Responder;

final class CommandDispatcher
{
    private const INTERNAL_TOKEN = '__LUMYNUS_KERNEL__byWelenySantos';

    private function __construct(string $token)
    {
        if ($token !== self::INTERNAL_TOKEN) {
            throw new RuntimeException(
                'CommandDispatcher can only be instantiated by the framework.'
            );
        }
    }

    /**
     * Inicializa o dispatcher.
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
    private function dispatch(array $argv): void
    {
        $argv = array_values($argv);
        $terminal = new ArgvTerminal($argv);

        $classInput = $terminal->command();

        if (!$classInput) {
            throw new RuntimeException('No command class informed.');
        }

        $methodInput = $terminal->method();

        if ($methodInput !== null && str_starts_with($methodInput, '--')) {
            $methodInput = substr($methodInput, 2);
        }

        $params = $terminal->params();

        $class = $this->resolveCommandClass($classInput);

        if (!class_exists($class)) {
            throw new RuntimeException("Command {$class} not found.");
        }

        $ref = new ReflectionClass($class);

        if (!$ref->isSubclassOf(LumynusCommands::class)) {
            throw new RuntimeException("{$class} is not a valid Command.");
        }

        $instance = $ref->newInstance();

        // Se nenhum método foi informado, chama handle()
        if ($methodInput === null) {
            if (!$ref->hasMethod('handle')) {
                throw new RuntimeException("{$class} must implement handle().");
            }

            $this->invokeMethod(
                $instance,
                $ref->getMethod('handle'),
                $terminal,
                $params
            );

            return;
        }

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

        $this->invokeMethod(
            $instance,
            $method,
            $terminal,
            $params
        );
    }

    /**
     * Resolve argumentos automaticamente com base na assinatura do método.
     */
    private function invokeMethod(
        object $instance,
        ReflectionMethod $method,
        Terminal $terminal,
        array $params
    ): void {
        $args = [];

        foreach ($method->getParameters() as $parameter) {
            $type = $parameter->getType()?->getName();

            if ($type === Terminal::class) {
                $args[] = $terminal;
                continue;
            }

            if ($type === Responder::class) {
                // o próprio Command é o responder
                $args[] = $instance;
                continue;
            }

            // fallback clássico
            $args[] = $params;
        }

        $method->invokeArgs($instance, $args);
    }


    /**
     * Resolve o nome da classe do comando a partir do input do terminal.
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

        $normalized = str_replace([':', '-', '_'], ' ', $input);
        $class = str_replace(' ', '', ucwords($normalized));

        if (!str_ends_with($class, 'Command')) {
            $class .= 'Command';
        }

        return 'App\\Commands\\' . $class;
    }
}
