<?php

declare(strict_types=1);

namespace Lumynus\Bundle\Contracts;

/**
 * Interface que define as operações de uma sessão segura.
 */
interface SessionInterface
{
    public function set(string $key, mixed $value): void;

    public function get(string $key): mixed;

    public function has(string $key): bool;

    public function remove(string $key): void;

    public function clear(): void;

    public function regenerate(): void;

    public function getId(): string;

    public function getAll(): array;

    public function createConfig(callable $callback): void;
}
