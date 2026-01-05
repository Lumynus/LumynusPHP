<?php

declare(strict_types=1);

namespace Lumynus\Bundle\Framework;

use Lumynus\Bundle\Framework\LumaClasses;

final class Resolver extends LumaClasses
{
    /** @var array<string,mixed> Results of executed methods */
    protected array $methodResults = [];

    /** @var array<string,array> Cached method names per class */
    protected array $methodCache = [];

    /**
     * Instantiate a class and fill attributes + call methods
     *
     * @param string $class
     * @param array<int|string,mixed> $params
     * @return object
     * @throws Exception
     */
    public function make(string $class, array $params = []): object
    {
        if (!class_exists($class)) {
            throw new \Exception("Class {$class} does not exist.");
        }

        $instance = new $class();

        // Cache method names per class for fast lookup
        $classMethods = $this->methodCache[$class] ??= get_class_methods($instance);

        foreach ($params as $key => $value) {
            if (is_int($key)) {
                // numeric: either method name or single attribute
                if (in_array($value, $classMethods, true)) {
                    $this->callMethod($instance, $value, null);
                } else {
                    $instance->$value = null; // default null if attribute exists later
                }
            } else {
                if (property_exists($instance, $key)) {
                    $instance->$key = $value;
                } elseif (in_array($key, $classMethods, true)) {
                    $this->callMethod($instance, $key, $value);
                } else {
                    // try setter
                    $setter = 'set' . ucfirst($key);
                    if (in_array($setter, $classMethods, true)) {
                        $this->callMethod($instance, $setter, $value);
                    } else {
                        throw new \Exception("Attribute or method '{$key}' not found in class {$class}");
                    }
                }
            }
        }

        // Var_dump friendly: merge attributes + method results
        $instance->__debugInfo = function() use ($instance) {
            $data = get_object_vars($instance);
            unset($data['__debugInfo']);
            return array_merge($data, $this->methodResults);
        };

        return $instance;
    }

    /**
     * Call a method with optional args and store result
     *
     * @param object $instance
     * @param string $method
     * @param mixed|null $args
     * @return void
     */
    protected function callMethod(object $instance, string $method, mixed $args = null): void
    {
        // Build arguments array
        $arguments = [];
        if (is_array($args)) {
            $assoc = array_keys($args) !== range(0, count($args) - 1);
            if ($assoc) {
                // named parameters
                $reflection = new \ReflectionMethod($instance, $method);
                foreach ($reflection->getParameters() as $param) {
                    if (array_key_exists($param->getName(), $args)) {
                        $arguments[] = $args[$param->getName()];
                    } elseif ($param->isDefaultValueAvailable()) {
                        $arguments[] = $param->getDefaultValue();
                    } else {
                        throw new \Exception("Required parameter '{$param->getName()}' not provided in {$method}()");
                    }
                }
            } else {
                $arguments = $args; // positional
            }
        } elseif ($args !== null) {
            $arguments = [$args];
        }

        $result = call_user_func_array([$instance, $method], $arguments);

        // store result
        $reflection = new \ReflectionMethod($instance, $method);
        if ($reflection->hasReturnType() && $reflection->getReturnType()->getName() === 'void') {
            $this->methodResults[$method] = "void executed";
        } else {
            $this->methodResults[$method] = $result;
        }
    }

    /**
     * Get executed method results
     *
     * @return array<string,mixed>
     */
    public function getMethodResults(): array
    {
        return $this->methodResults;
    }
}
