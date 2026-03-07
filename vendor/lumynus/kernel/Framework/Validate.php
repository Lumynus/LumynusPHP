<?php

declare(strict_types=1);

/**
 * @author WelenySantos de Oliveira <welenysantos@gmail.com>
 * @package Lumynus\Framework
 */

namespace Lumynus\Framework;

use Lumynus\Framework\LumaClasses;

final class Validate extends LumaClasses
{

    /**
     * Valida se os campos existem e seguem as regras definidas.
     *
     * @param array $keys Regras de validação no formato 'campo: regra1, regra2'
     * @param array $data Dados a serem validados
     * @return array Retorna um array com o status da validação, campos faltantes e erros encontrados
     */
    public function exists(array $keys, array $data): array
    {
        $rules = $this->parseRules($keys);
        $errors = [];
        $missing = [];

        foreach ($rules as $field => $rule) {
            $valueExists = array_key_exists($field, $data);
            $value = $valueExists ? $data[$field] : null;

            if ($rule['required'] && !$valueExists) {
                $missing[] = $field;
                $errors[$field][] = 'This field is required.';
                continue;
            }

            if (!$valueExists) {
                continue;
            }

            if ($value === '' || is_null($value)) {
                if ($rule['nullable']) {
                    continue;
                }

                $errors[$field][] = 'This field cannot be null.';
                continue;
            }

            if (isset($rule['type']) && !$this->isTypeValid($value, $rule['type'])) {
                $errors[$field][] = "The value is not a valid {$rule['type']}.";
                continue;
            }

            if (isset($rule['min'])) {
                if (($rule['type'] ?? null) === 'string' && mb_strlen((string) $value) < $rule['min']) {
                    $errors[$field][] = "Minimum length is {$rule['min']} characters.";
                } elseif (is_numeric($value) && $value < $rule['min']) {
                    $errors[$field][] = "Minimum allowed value is {$rule['min']}.";
                }
            }

            if (isset($rule['max'])) {
                if (($rule['type'] ?? null) === 'string') {
                    if (mb_strlen((string) $value) > $rule['max']) {
                        $errors[$field][] = "Maximum length is {$rule['max']} characters.";
                    }
                } elseif (is_numeric($value) && $value > $rule['max']) {
                    $errors[$field][] = "Maximum allowed value is {$rule['max']}.";
                }
            }

            if (isset($rule['regex']) && !preg_match('/' . $rule['regex'] . '/', (string) $value)) {
                $errors[$field][] = "The value format is invalid.";
            }

            if (isset($rule['type']) && $rule['type'] === 'array' && isset($rule['subtype']) && is_array($value)) {
                foreach ($value as $i => $item) {
                    if (!$this->isTypeValid($item, $rule['subtype'])) {
                        $errors[$field][] = "Item $i must be of type {$rule['subtype']}.";
                    }
                }
            }

            if (isset($rule['in'])) {
                if (!in_array((string) $value, $rule['in'], true)) {
                    $errors[$field][] = 'The value is not in the allowed list.';
                }
            }
        }

        foreach ($data as $key => $value) {
            if (!array_key_exists($key, $rules)) {
                $errors[$key][] = 'Unexpected field.';
            }
        }

        return [
            'success' => empty($errors),
            'missing' => $missing,
            'errors'  => $errors,
        ];
    }

    /**
     * Verifica se os campos existem e seguem as regras definidas.
     *
     * @param array $keys Regras de validação no formato 'campo: regra1, regra2'
     * @param array $data Dados a serem validados
     * @return bool Retorna true se todos os campos forem válidos, false caso contrário
     */
    public function verify(array $keys, array $data): bool
    {
        $result = $this->exists($keys, $data);
        return $result['success'];
    }

    /**
     * Analisa as regras de validação.
     */
    private function parseRules(array $rawRules): array
    {
        $parsed = [];

        $validTypes = [
            'string',
            'int',
            'bool',
            'array',
            'object',
            'float',
            'date',
            'datetime',
            'json',
            'xml',
            'ini'
        ];

        foreach ($rawRules as $ruleString) {
            [$field, $ruleDefs] = explode(':', $ruleString, 2);
            $field = trim($field);

            $rulesRaw = preg_split('/,(?![^(]*\))/', $ruleDefs);

            $rules = array_unique(
                array_map('trim', $rulesRaw)
            );

            $parsed[$field] = [
                'required' => false,
                'nullable' => false,
            ];

            foreach ($rules as $rule) {

                $rule = trim($rule);

                if (preg_match('/^in\((.+)\)$/i', $rule, $m)) {
                    $parsed[$field]['in'] = array_map('trim', explode(',', $m[1]));
                    continue;
                }

                if (preg_match('/^regex\((.+)\)$/i', $rule, $m)) {
                    $parsed[$field]['regex'] = $m[1];
                    continue;
                }

                $ruleLower = strtolower($rule);

                if (str_starts_with($ruleLower, 'min')) {
                    $parsed[$field]['min'] = (int) substr($ruleLower, 3);
                    continue;
                }

                if (str_starts_with($ruleLower, 'max')) {
                    $parsed[$field]['max'] = (int) substr($ruleLower, 3);
                    continue;
                }

                if (in_array($ruleLower, ['required', 'not null'])) {
                    $parsed[$field]['required'] = true;
                    continue;
                }

                if (in_array($ruleLower, ['null', 'nullable'])) {
                    $parsed[$field]['nullable'] = true;
                    continue;
                }

                if (preg_match('/^array<(.+)>$/i', $rule, $m)) {
                    $parsed[$field]['type'] = 'array';
                    $parsed[$field]['subtype'] = strtolower($m[1]);
                    continue;
                }
                if (in_array($ruleLower, $validTypes)) {
                    $parsed[$field]['type'] = $ruleLower;
                }
            }
        }

        return $parsed;
    }

    /**
     * Verifica se o valor é do tipo esperado.
     */
    private function isTypeValid(mixed $value, string $type): bool
    {
        return match ($type) {
            'string'   => is_string($value),
            'int'      => is_int($value),
            'float'    => is_float($value) || is_int($value),
            'bool'     => is_bool($value),
            'array'    => is_array($value),
            'object'   => is_object($value),
            'date'     => $this->isValidDate($value, 'Y-m-d'),
            'datetime' => $this->isValidDate($value, 'Y-m-d H:i:s'),
            'json'     => $this->isValidJson($value),
            'xml'      => $this->isValidXml($value),
            'ini'      => $this->isValidIni($value),
            default    => false,
        };
    }

    /**
     * Helper para Data
     */
    private function isValidDate(mixed $value, string $format): bool
    {
        if (!is_string($value)) return false;
        $dt = \DateTime::createFromFormat($format, $value);
        return $dt && $dt->format($format) === $value;
    }

    /**
     * Helper para JSON
     */
    private function isValidJson(mixed $value): bool
    {
        if (!is_string($value)) return false;

        if (function_exists('json_validate')) {
            return json_validate($value);
        }

        json_decode($value);
        return json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * Helper para XML
     */
    private function isValidXml(mixed $value): bool
    {
        if (!is_string($value)) return false;

        $previousState = libxml_use_internal_errors(true);

        $xml = simplexml_load_string($value);
        $errors = libxml_get_errors();

        libxml_clear_errors();
        libxml_use_internal_errors($previousState);

        return $xml !== false && empty($errors);
    }

    /**
     * Helper para INI
     */
    private function isValidIni(mixed $value): bool
    {
        if (!is_string($value)) return false;
        return @parse_ini_string($value) !== false;
    }
}
