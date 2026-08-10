<?php

declare(strict_types=1);

/**
 * @author Weleny Santos <welenysantos@gmail.com>
 * @package Lumynus\Framework
 */

namespace Lumynus\Framework;

use Lumynus\Framework\LumaClasses;
use Lumynus\Framework\Logs;

class Collection extends LumaClasses
{
    public function __construct(private array $items) {}

    /**
     * Verifica se a chave existe na collection.
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        return $this->hasRecursive($this->items, $key);
    }

    /**
     * Verifica se a chave existe na collection.
     * @param string $key
     * @return bool
     */
    public function exists(string $key): bool
    {
        return $this->has($key);
    }

    /**
     * Busca por uma chave na collection.
     * @param string $key
     * @param bool $recursive
     * @return array
     */
    /**
     * Busca por uma chave na collection.
     * @param string $key
     * @param bool $recursive
     * @return array
     */
    public function search(string $key, bool $recursive = false): array
    {
        return $this->searchArray($this->items, $key, $recursive);
    }

    /**
     * Atualiza uma chave na collection.
     * @param string $key
     * @param mixed $newValue
     * @return bool
     */
    public function update(string $key, mixed $newValue): bool
    {
        if (!array_key_exists($key, $this->items)) {
            return false;
        }

        $this->items[$key] = $newValue;
        return true;
    }

    /**
     * Atualiza todas as chaves da collection.
     * @param string $key
     * @param mixed $newValue
     * @return bool
     */
    public function updateAll(string $key, mixed $newValue): bool
    {
        return $this->updateRecursive($this->items, $key, $newValue);
    }

    /**
     * Remove uma chave da collection.
     * @param string $key
     * @return bool
     */
    /**
     * Remove uma chave da collection.
     * @param string $key
     * @return bool
     */
    /**
     * Remove uma chave da collection.
     * @param string $key
     * @return bool
     */
    public function remove(string $key): bool
    {
        if (!array_key_exists($key, $this->items)) {
            return false;
        }

        unset($this->items[$key]);

        return true;
    }

    /**
     * Remove todas as chaves da collection.
     * @param string $key
     * @return bool
     */
    public function removeAll(string $key): bool
    {
        return $this->removeRecursive($this->items, $key);
    }

    /**
     * Adiciona uma chave à collection.
     * @param string $key
     * @param mixed $value
     * @return bool
     */
    public function set(string $key, mixed $value): bool
    {
        if (array_key_exists($key, $this->items)) {
            Logs::register("Collection", "Key $key already exists in the collection");
            return false;
        }

        $this->items[$key] = $value;

        return true;
    }

    /**
     * Busca por uma chave na collection.
     * @param string $key
     * @return mixed
     */
    public function get(string $key): mixed
    {
        $data = $this->searchArray($this->items, $key, true);
        return $data[0] ?? null;
    }

    /**
     * Busca por todas as chaves da collection.
     * @param string $key
     * @return array
     */
    public function getAllByKey(string $key): array
    {
        return $this->searchArray($this->items, $key, true);
    }

    /**
     * Retorna todos os itens da collection.
     * @return array
     */
    public function getAll(): array
    {
        return $this->items;
    }

    /**
     * Retorna a quantidade de itens da collection.
     * @return int
     */
    public function count(): int
    {
        return count($this->items);
    }

    /**
     * Busca por uma chave na collection.
     * @param array $items
     * @param string $key
     * @param bool $recursive
     * @return array
     */
    private function searchArray(array $items, string $key, bool $recursive): array
    {
        $located = [];

        foreach ($items as $keyInternal => $value) {
            if ((string)$keyInternal === $key) {
                $located[] = $value;
            }

            if ($recursive && is_array($value)) {
                $located = array_merge(
                    $located,
                    $this->searchArray($value, $key, true)
                );
            }
        }

        return $located;
    }

    /**
     * Atualiza uma chave na collection.
     * @param array $items
     * @param string $key
     * @param mixed $newValue
     * @return bool
     */
    private function updateRecursive(array &$items, string $key, mixed $newValue): bool
    {
        $updated = false;

        foreach ($items as $currentKey => &$value) {

            if ((string)$currentKey === $key) {
                $value = $newValue;
                $updated = true;
            }

            if (is_array($value)) {
                $updated = $this->updateRecursive($value, $key, $newValue) || $updated;
            }
        }

        return $updated;
    }

    /**
     * Remove uma chave na collection.
     * @param array $items
     * @param string $key
     * @return bool
     */
    private function removeRecursive(array &$items, string $key): bool
    {
        $removed = false;

        foreach ($items as $currentKey => &$value) {

            if ((string)$currentKey === $key) {
                unset($items[$currentKey]);
                $removed = true;
                continue;
            }

            if (is_array($value)) {
                $removed = $this->removeRecursive($value, $key) || $removed;
            }
        }

        return $removed;
    }

    /**
     * Verifica se uma chave existe na collection.
     * @param array $items
     * @param string $key
     * @return bool
     */
    private function hasRecursive(array $items, string $key): bool
    {
        foreach ($items as $currentKey => $value) {

            if ((string)$currentKey === $key) {
                return true;
            }

            if (is_array($value) && $this->hasRecursive($value, $key)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Método para obter a instância da classe Luma.
     * @return Luma Retorna uma nova instância da classe Luma.
     */
    public function __debugInfo(): array
    {
        return [
            'Lumynus' => "Framework PHP"
        ];
    }
}
