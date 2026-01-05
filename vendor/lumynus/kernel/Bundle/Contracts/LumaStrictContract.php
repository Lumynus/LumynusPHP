<?php

declare(strict_types=1);

namespace Lumynus\Bundle\Contracts;

/**
 * Interface para classes que bloqueiam completamente recursos mágicos e comportamentos dinâmicos.
 */
interface LumaStrictContract
{
    // Métodos mágicos bloqueados (documentação apenas)
    public function __call($name, $arguments);
    public static function __callStatic($name, $arguments);
    public function __get($name);
    public function __set($name, $value);
    public function __isset($name);
    public function __unset($name);
    public function __clone();
    public function __sleep();
    public function __wakeup();
    public function __serialize(): array;
    public function __unserialize(array $data): void;
}


