<?php

declare(strict_types=1);

namespace Lumynus\Bundle\Framework;

use Lumynus\Bundle\Framework\LumaClasses;

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

            if (is_null($value)) {
                if (!$rule['nullable']) {
                    $errors[$field][] = 'This field cannot be null.';
                }
                continue;
            }

            if (isset($rule['type']) && !$this->isTypeValid($value, $rule['type'])) {
                $errors[$field][] = "The value is not a valid {$rule['type']}.";
                continue;
            }

            if (isset($rule['min'])) {
                if (is_string($value) && mb_strlen($value) < $rule['min']) {
                    $errors[$field][] = "Minimum length is {$rule['min']} characters.";
                } elseif (is_numeric($value) && $value < $rule['min']) {
                    $errors[$field][] = "Minimum allowed value is {$rule['min']}.";
                }
            }

            if (isset($rule['max'])) {
                if ($rule['type'] === 'string') {
                    $stringValue = (string) $value;
                    if (mb_strlen($stringValue) > $rule['max']) {
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
            $rules = array_map('trim', explode(',', $ruleDefs));

            $parsed[$field] = [
                'required' => false,
                'nullable' => false,
            ];

            foreach ($rules as $rule) {
                $rule = strtolower($rule);

                if (str_starts_with($rule, 'min')) {
                    $parsed[$field]['min'] = (int) str_replace('min', '', $rule);
                } elseif (str_starts_with($rule, 'max')) {
                    $parsed[$field]['max'] = (int) str_replace('max', '', $rule);
                } elseif (in_array($rule, ['null', 'nullable'])) {
                    $parsed[$field]['nullable'] = true;
                } elseif (in_array($rule, ['not null', 'required'])) {
                    $parsed[$field]['required'] = true;
                } elseif (in_array($rule, $validTypes)) {
                    $parsed[$field]['type'] = $rule;
                }

                if (str_starts_with($rule, 'regex(') && str_ends_with($rule, ')')) {
                    $pattern = substr($rule, 6, -1);
                    $parsed[$field]['regex'] = $pattern;
                }

                if (str_starts_with($rule, 'array<') && str_ends_with($rule, '>')) {
                    $subtype = substr($rule, 6, -1);
                    $parsed[$field]['type'] = 'array';
                    $parsed[$field]['subtype'] = $subtype;
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
