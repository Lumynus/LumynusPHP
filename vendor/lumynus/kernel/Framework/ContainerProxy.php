<?php

declare(strict_types=1);

/**
 * @author Weleny Santos <welenysantos@gmail.com>
 * @package Lumynus\Framework
 */

namespace Lumynus\Framework;

use Lumynus\Framework\LumynusContainer;

final class ContainerProxy
{
    /**
     * Método para obter a instância da classe solicitada.
     * 
     * Útil, pois permite reutilizar a mesma instância da classe em diferentes partes do código.
     * 
     * @param string $class O namespace da classe a ser instanciada.
     * @param array $options Argumentos a serem passados para o construtor.
     * @param string|null $key Chave de identificação única no container.
     * @return object Retorna a instância da classe.
     */
    public function make(string $class, array $options = [], ?string $key = null): object
    {
        return LumynusContainer::resolve($class, $options, $key);
    }

    /**
     * Método para obter o trace de chamadas.
     * @return string Retorna o trace de chamadas.
     */
    public function getTrace(): string
    {
        return LumynusContainer::getTrace();
    }
}
