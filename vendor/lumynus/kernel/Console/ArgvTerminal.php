<?php

declare(strict_types=1);

namespace Lumynus\Console;

use Lumynus\Console\Contracts\Terminal;

final class ArgvTerminal implements Terminal
{
    public function __construct(private array $argv)
    {
        $this->argv = array_values($argv);
    }

    /**
     * Retorna todos os argumentos crus do terminal.
     */
    public function getAll(): array
    {
        return $this->argv;
    }

    /**
     * Retorna o nome do comando informado.
     */
    public function command(): string
    {
        return $this->argv[0] ?? '';
    }

    /**
     * Retorna o método do comando, se informado.
     * Remove o prefixo "--" quando existir.
     */
    public function method(): ?string
    {
        $method = $this->argv[1] ?? null;

        if ($method && str_starts_with($method, '--')) {
            return substr($method, 2);
        }

        return $method;
    }

    /**
     * Retorna apenas os parâmetros do comando.
     */
    public function params(): array
    {
        return array_slice($this->argv, 2);
    }
}
