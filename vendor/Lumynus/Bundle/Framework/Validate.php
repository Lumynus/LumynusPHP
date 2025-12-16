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

            // Required field not provided
            if ($rule['required'] && !$valueExists) {
                $missing[] = $field;
                $errors[$field][] = 'This field is required.';
                continue;
            }

            // Optional field not provided - okay
            if (!$valueExists) {
                continue;
            }

            // Value is null but field does not allow null
            if (is_null($value) && !$rule['nullable']) {
                $errors[$field][] = 'This field cannot be null.';
                continue;
            }

            // Type validation
            if (isset($rule['type']) && !$this->isTypeValid($value, $rule['type'])) {
                $errors[$field][] = "The type must be {$rule['type']}.";
                continue;
            }

            // Minimum validation
            if (isset($rule['min'])) {
                if (is_string($value) && mb_strlen($value) < $rule['min']) {
                    $errors[$field][] = "Minimum length is {$rule['min']} characters.";
                } elseif (is_numeric($value) && $value < $rule['min']) {
                    $errors[$field][] = "Minimum allowed value is {$rule['min']}.";
                }
            }

            // Maximum validation
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

            // Regex validation
            if (isset($rule['regex']) && !preg_match('/' . $rule['regex'] . '/', (string) $value)) {
                $errors[$field][] = "The value format is invalid.";
            }

            if ($rule['type'] === 'array' && isset($rule['subtype'])) {
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
     * Analisa as regras de validação fornecidas e as converte em um formato estruturado.
     *
     * @param array $rawRules Regras no formato 'campo: regra1, regra2'
     * @return array Retorna um array estruturado com as regras de validação
     */
    private function parseRules(array $rawRules): array
    {
        $parsed = [];

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
                } elseif (in_array($rule, ['string', 'int', 'bool', 'array', 'date', 'datetime', 'float'])) {
                    $parsed[$field]['type'] = $rule;
                }
                if (str_starts_with($rule, 'regex(') && str_ends_with($rule, ')')) {
                    $pattern = substr($rule, 6, -1); // remove "regex(" do início e ")" do fim
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
     *
     * @param mixed $value Valor a ser verificado
     * @param string $type Tipo esperado ('string', 'int', 'bool', 'array', 'date', 'datetime')
     * @return bool Retorna true se o tipo for válido, false caso contrário
     */
    private function isTypeValid($value, string $type): bool
    {
        return match ($type) {
            'string'   => is_string($value),
            'int'      => is_int($value),
            'bool'     => is_bool($value),
            'array'    => is_array($value),
            'date'     => $this->isValidDate($value, 'Y-m-d'),
            'datetime' => $this->isValidDate($value, 'Y-m-d H:i:s'),
            'float' => is_float($value),
            default    => false,
        };
    }

    /**
     * Verifica se uma string é uma data válida no formato especificado.
     *
     * @param string $value String a ser verificada
     * @param string $format Formato da data (ex: 'Y-m-d' ou 'Y-m-d H:i:s')
     * @return bool Retorna true se a string for uma data válida, false caso contrário
     */
    private function isValidDate($value, string $format): bool
    {
        $dt = \DateTime::createFromFormat($format, $value);
        return $dt && $dt->format($format) === $value;
    }
}
