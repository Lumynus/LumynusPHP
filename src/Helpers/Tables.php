<?php

namespace App\Helpers;

use Lumynus\Bundle\Framework\Sanitizer;


/**
 * Trait com métodos auxiliares para manipulação de tabelas, paginação, ordenação e filtros.
 * Pode ser usada em qualquer classe que precise dessas funcionalidades.
 * Exemplo de uso:
 * ```php
 * @example class MinhaClasse {
 *   use Tables;
 *  // ...
 * }
 * 
 */

trait Tables
{

    /**
     * Calcula os valores de limite e offset para paginação com base nos dados fornecidos.
     *
     * @param array $request Os dados de entrada, geralmente $_GET ou $_POST.
     * @param string $type O tipo de dados a serem processados ("GET" ou "POST"). Padrão é "GET".
     * @return array Retorna um array associativo com 'limit' e 'offset'.
     */
    public function pagination(array $request, string $type = "GET"): array
    {
        $page  = isset($request[$type]['page']) ? max(1, (int)$request[$type]['page']) : 1;
        $total = isset($request[$type]['total']) ? (int)$request[$type]['total'] : 10;
        $offset = ($page - 1) * $total;
        return [
            'limit' => (int)$total,
            'offset' => (int)$offset
        ];
    }

    /**
     * Constrói a cláusula ORDER BY para consultas SQL com base nos dados fornecidos.
     *
     * @param array $request Os dados de entrada, geralmente $_GET ou $_POST.
     * @param string $type O tipo de dados a serem processados ("GET" ou "POST"). Padrão é "GET".
     * @return string Retorna a cláusula ORDER BY formatada ou uma string vazia se não houver ordenação.
     */
    public function orderby(array $request, string $type = "GET"): string
    {
        $orderBy = isset($request[$type]['orderby']) ? $request[$type]['orderby'] : '';
        $order = isset($request[$type]['order']) ? strtoupper($request[$type]['order']) : 'ASC';
        if (!in_array($order, ['ASC', 'DESC'])) {
            $order = 'ASC';
        }

        return (string) Sanitizer::string($orderBy) . ' ' . (string) Sanitizer::string($order) === ' '
            ? '' : ' ' . (string) Sanitizer::string($orderBy) . ' ' . (string) strtoupper(Sanitizer::string($order)) . '';
    }

    /**
     * Extrai os filtros dos dados fornecidos.
     *
     * @param array $request Os dados de entrada, geralmente $_GET ou $_POST.
     * @param string $type O tipo de dados a serem processados ("GET" ou "POST"). Padrão é "INPUT".
     * @return array Retorna um array associativo com os filtros extraídos.
     */
    public function filters(array $request, string $type = "INPUT"): array
    {
        if (empty($request[$type]['filters'])) {
            return [];
        }
        $filters = $request[$type]['filters'];
        return $filters;
    }
}
