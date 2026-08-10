<?php

declare(strict_types=1);

namespace Lumynus\Contracts;

/**
 * Interface que define as operações de um cookie seguro.
 */
interface CookieInterface
{
    /**
     * Define um cookie.
     *
     * @param string $key Chave do cookie.
     * @param mixed $value Valor do cookie.
     * @return void
     */
    public function set(string $key, mixed $value): void;

    /**
     * Obtém o valor de um cookie.
     *
     * @param string $key Chave do cookie.
     * @return mixed Valor do cookie.
     */
    public function get(string $key): mixed;

    /**
     * Verifica se um cookie existe.
     *
     * @param string $key Chave do cookie.
     * @return bool True se o cookie existir, false caso contrário.
     */
    public function has(string $key): bool;

    /**
     * Remove um cookie.
     *
     * @param string $key Chave do cookie.
     * @return void
     */
    public function remove(string $key): void;

    /**
     * Remove todos os cookies.
     *
     * @return void
     */
    public function clear(): void;

    /**
     * Regenera o ID do cookie.
     *
     * @return void
     */
    public function regenerate(): void;

    /**
     * Obtém o ID do cookie.
     *
     * @return string ID do cookie.
     */
    public function getId(): string;

    /**
     * Obtém todos os cookies.
     *
     * @return array Array com todos os cookies.
     */
    public function getAll(): array;
}
