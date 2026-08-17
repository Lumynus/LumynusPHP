<?php

declare(strict_types=1);

namespace Lumynus\Console\Contracts;

interface Terminal
{
    /**
     * Retorna todos os argumentos passados para o comando.
     *
     * @return array
     */
    public function getAll(): array;

    /**
     * Retorna o comando principal.
     *
     * @return string
     */
    public function command(): string;

    /**
     * Retorna o método chamado.
     *
     * @return string|null
     */
    public function method(): ?string;

    /**
     * Retorna os parâmetros do comando.
     *
     * @return array
     */
    public function params(): array;
}
