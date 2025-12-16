<?php

declare(strict_types=1);

namespace Lumynus\Bundle\Framework;

use Lumynus\Bundle\Contracts\LumaStrictContract;

abstract class LumaClasses implements LumaStrictContract
{

    public const VERSION = '1.1.0';

    public function __call($name, $arguments)
    {
        throw new \BadMethodCallException("Magic method __call is not allowed: {$name}()");
    }

    public static function __callStatic($name, $arguments)
    {
        throw new \BadMethodCallException("Magic method __callStatic is not allowed: {$name}()");
    }

    public function __get($name)
    {
        throw new \RuntimeException("Magic access to undefined property __get is not allowed: \${$name}");
    }

    public function __set($name, $value)
    {
        throw new \RuntimeException("Magic assignment __set is not allowed: \${$name}");
    }

    public function __isset($name)
    {
        throw new \RuntimeException("Magic isset __isset is not allowed on: \${$name}");
    }

    public function __unset($name)
    {
        throw new \RuntimeException("Magic unset __unset is not allowed on: \${$name}");
    }

    public function __clone()
    {
        throw new \RuntimeException("Cloning this class is not allowed (__clone blocked).");
    }

    public function __sleep()
    {
        throw new \RuntimeException("Serialization is not allowed (__sleep blocked).");
    }

    public function __wakeup()
    {
        throw new \RuntimeException("Deserialization is not allowed (__wakeup blocked).");
    }

    public function __serialize(): array
    {
        throw new \RuntimeException("Serialization is not allowed (__serialize blocked).");
    }

    public function __unserialize(array $data): void
    {
        throw new \RuntimeException("Deserialization is not allowed (__unserialize blocked).");
    }
}
