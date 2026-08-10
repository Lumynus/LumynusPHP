<?php

declare(strict_types=1);

namespace Lumynus\Contracts;

/**
 * Interface que define as operações de uma sessão segura.
 */
interface SessionInterface
{
    /**
     * Define um valor na sessão.
     *
     * @param string $key Chave da sessão.
     * @param mixed $value Valor da sessão.
     * @return void
     */
    public function set(string $key, mixed $value): void;

    /**
     * Obtém o valor de uma chave da sessão.
     *
     * @param string $key Chave da sessão.
     * @return mixed Valor da sessão.
     */
    public function get(string $key): mixed;

    /**
     * Verifica se uma chave existe na sessão.
     *
     * @param string $key Chave da sessão.
     * @return bool True se a chave existir, false caso contrário.
     */
    public function has(string $key): bool;

    /**
     * Remove uma chave da sessão.
     *
     * @param string $key Chave da sessão.
     * @return void
     */
    public function remove(string $key): void;

    /**
     * Remove todas as chaves da sessão.
     *
     * @return void
     */
    public function clear(): void;

    /**
     * Regenera o ID da sessão.
     *
     * @return void
     */
    public function regenerate(): void;

    /**
     * Obtém o ID da sessão.
     *
     * @return string ID da sessão.
     */
    public function getId(): string;

    /**
     * Obtém todos os dados da sessão.
     *
     * @return array Array com todos os dados da sessão.
     */
    public function getAll(): array;
}
