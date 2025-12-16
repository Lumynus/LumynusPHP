<?php

declare(strict_types=1);

namespace Lumynus\Bundle\Framework;
use Lumynus\Bundle\Framework\LumaClasses;

final class Sanitizer extends LumaClasses
{
    /**
     * Limpa uma string, removendo tags HTML e, opcionalmente, escapando entidades HTML.
     *
     * @param string $input A string de entrada a ser sanitizada.
     * @param bool $escapeHtml Define se deve escapar HTML usando htmlspecialchars.
     * @return string A string sanitizada.
     */
    public static function string(string $input, bool $escapeHtml = true): string
    {
        $input = strip_tags($input);
        if ($escapeHtml) {
            $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
        }
        return trim($input);
    }

    /**
     * Remove todos os caracteres não numéricos e retorna o inteiro.
     *
     * @param int|string|float $input O valor a ser sanitizado.
     * @return int O valor convertido em inteiro.
     */
    public static function int(int|string|float $input): int
    {
        return (int) filter_var($input, FILTER_SANITIZE_NUMBER_INT);
    }

    /**
     * Remove caracteres inválidos de um número decimal e retorna o float.
     *
     * @param int|string|float $input O valor a ser sanitizado.
     * @return float O valor convertido em float.
     */
    public static function float(int|string|float $input): float
    {
        return (float) filter_var($input, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    }

    /**
     * Remove caracteres inválidos de um e-mail.
     *
     * @param string $input O e-mail de entrada.
     * @return string O e-mail sanitizado.
     */
    public static function email(string $input): string
    {
        return filter_var($input, FILTER_SANITIZE_EMAIL);
    }

    /**
     * Remove caracteres inválidos de uma URL.
     *
     * @param string $input A URL de entrada.
     * @return string A URL sanitizada.
     */
    public static function url(string $input): string
    {
        return filter_var($input, FILTER_SANITIZE_URL);
    }

    /**
     * Converte valores como "1", "true", "on" para booleano.
     *
     * @param mixed $input O valor de entrada.
     * @return bool O valor convertido em booleano.
     */
    public static function boolean(mixed $input): bool
    {
        return filter_var($input, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false;
    }

    /**
     * Sanitiza um array recursivamente aplicando o tipo de sanitização definido.
     *
     * @param array $input Array a ser sanitizado.
     * @param string $type Tipo de sanitização: 'string', 'int', 'float', 'email', 'url', 'bool'.
     * @param bool $escapeHtml Se for do tipo 'string', define se escapa HTML.
     * @return array Array sanitizado com todos os valores convertidos.
     */
    public static function array(array $input, string $type = 'string', bool $escapeHtml = true): array
    {
        return array_map(function ($value) use ($type, $escapeHtml) {
            if (is_array($value)) {
                return self::array($value, $type, $escapeHtml);
            }

            switch ($type) {
                case 'string':
                    return self::string((string) $value, $escapeHtml);
                case 'int':
                    return self::int($value);
                case 'float':
                    return self::float($value);
                case 'email':
                    return self::email((string) $value);
                case 'url':
                    return self::url((string) $value);
                case 'bool':
                    return self::boolean($value);
                default:
                    throw new \InvalidArgumentException("Tipo de sanitização inválido: {$type}");
            }
        }, $input);
    }

    /**
     * Remove espaços, quebras de linha e substitui múltiplos espaços por apenas um.
     *
     * @param string $input A string de entrada.
     * @return string A string com espaços normalizados.
     */
    public static function normalizeWhitespace(string $input): string
    {
        return preg_replace('/\s+/', ' ', trim($input));
    }

    /**
     * Remove todos os números de uma string.
     *
     * @param string $input A string de entrada.
     * @return string A string sem números.
     */
    public static function removeNumbers(string $input): string
    {
        return preg_replace('/\d+/', '', $input);
    }

    /**
     * Remove caracteres especiais, mantendo letras, números e espaços.
     *
     * @param string $input A string de entrada.
     * @return string A string sem caracteres especiais.
     */
    public static function removeSpecialCharacters(string $input): string
    {
        return preg_replace('/[^\w\s]/', '', $input);
    }

    /**
     * Remove todas as tags HTML e PHP.
     *
     * @param string $input A string de entrada.
     * @return string A string sem tags.
     */
    public static function removeHtmlTags(string $input): string
    {
        return strip_tags($input);
    }

    /**
     * Remove acentuação de caracteres latinos comuns.
     *
     * @param string $input A string de entrada.
     * @return string A string sem acentos.
     */
    public static function removeAccents(string $input): string
    {
        $unwanted_array = [
            'á' => 'a',
            'à' => 'a',
            'ã' => 'a',
            'â' => 'a',
            'ä' => 'a',
            'é' => 'e',
            'è' => 'e',
            'ê' => 'e',
            'ë' => 'e',
            'í' => 'i',
            'ì' => 'i',
            'î' => 'i',
            'ï' => 'i',
            'ó' => 'o',
            'ò' => 'o',
            'õ' => 'o',
            'ô' => 'o',
            'ö' => 'o',
            'ú' => 'u',
            'ù' => 'u',
            'û' => 'u',
            'ü' => 'u',
            'ç' => 'c',
            'ñ' => 'n'
        ];
        return strtr($input, $unwanted_array);
    }

    /**
     * Remove todas as letras, mantendo apenas números e símbolos.
     *
     * @param string $input A string de entrada.
     * @return string A string sem letras.
     */
    public static function removeLetters(string $input): string
    {
        return preg_replace('/[a-zA-Z]/', '', $input);
    }

    /**
     * Remove espaços duplicados em excesso.
     *
     * @param string $input A string de entrada.
     * @return string A string com espaços reduzidos.
     */
    public static function removeExtraSpaces(string $input): string
    {
        return preg_replace('/\s+/', ' ', trim($input));
    }

    /**
     * Remove quebras de linha e substitui por espaços simples.
     *
     * @param string $input A string de entrada.
     * @return string A string sem quebras de linha.
     */
    public static function removeLineBreaks(string $input): string
    {
        return str_replace(["\r", "\n"], ' ', $input);
    }

    /**
     * Método para obter a instância da classe Luma.
     * @return Luma Retorna uma nova instância da classe Luma.
     */
    public function __debugInfo():array
    {
        return [
            'Lumynus' => "Framework PHP"
        ];
    }
}
