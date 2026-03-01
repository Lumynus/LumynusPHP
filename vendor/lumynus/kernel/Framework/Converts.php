<?php

declare(strict_types=1);

namespace Lumynus\Framework;

use Lumynus\Framework\LumaClasses;

final class Converts extends LumaClasses
{

    /**
     * Converte uma string para um inteiro.
     *
     * @param string $input A string a ser convertida.
     * @return int|null O valor convertido em inteiro ou null se a entrada for null.
     */
    public static function toInt(string|null $input): int|null
    {
        if ($input === null) {
            return null;
        }
        return (int) $input;
    }

    /**
     * Converte uma string para um float.
     *
     * @param string $input A string a ser convertida.
     * @return float|null O valor convertido em float ou null se a entrada for null.
     */
    public static function toFloat(string|null $input): float|null
    {
        if ($input === null) {
            return null;
        }
        return (float) $input;
    }

    /**
     * Converte um valor para uma string.
     *
     * @param int|float|string|bool|null $input O valor a ser convertido.
     * @return string|null O valor convertido em string ou null se a entrada for null.
     */
    public static function toString(int|float|string|bool|null $input): string|null
    {
        if ($input === null) {
            return null;
        }
        return (string) $input;
    }

    /**
     * Converte um valor para um booleano.
     *
     * @param string|int|null $input O valor a ser convertido.
     * @return bool|null O valor convertido em booleano ou null se a entrada for null.
     */
    public static function toBoolean(string|int|null $input): bool|null
    {
        if ($input === null) {
            return null;
        }
        return filter_var($input, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false;
    }

    /**
     * Converte um objeto ou array em array associativo recursivamente.
     *
     * @param object|array|null $input Objeto ou array de entrada.
     * @return array|null Array resultante ou null se a entrada for null.
     */
    public static function toArray(object|array|null $input): array|null
    {
        if ($input === null) {
            return null;
        }
        return json_decode(json_encode($input), true);
    }

    /**
     * Converte um array ou objeto em um objeto (stdClass) recursivamente.
     *
     * @param array|object|null $input Array ou objeto de entrada.
     * @return object|null Objeto resultante ou null se a entrada for null.
     */
    public static function toObject(array|object|null $input): object|null
    {
        if ($input === null) {
            return null;
        }
        return json_decode(json_encode($input));
    }

    /**
     * Converte um JSON em um array associativo.
     *
     * @param string|null $json A string JSON a ser convertida.
     * @return array|null O array resultante ou null se a entrada for null.
     * @throws \InvalidArgumentException Se o JSON for invalid.
     */
    public static function jsonToArray(string|null $json): array|null
    {
        if ($json === null) {
            return null;
        }
        $data = json_decode($json, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \InvalidArgumentException('Invalid JSON: ' . json_last_error_msg());
        }
        return $data;
    }

    /**
     * Converte um JSON em um objeto (stdClass).
     *
     * @param string|null $json A string JSON a ser convertida.
     * @return object|null O objeto resultante ou null se a entrada for null.
     * @throws \InvalidArgumentException Se o JSON for invalid.
     */
    public static function jsonToObject(string|null $json): object|null
    {
        if ($json === null) {
            return null;
        }
        $data = json_decode($json);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \InvalidArgumentException('Invalid JSON: ' . json_last_error_msg());
        }
        return $data;
    }

    /**
     * Converte uma string de data e hora para uma string de data no formato 'Y-m-d'.
     *
     * @param string|null $dateTimeString A string de data e hora a ser convertida.
     * @return string|null A string de data no formato 'Y-m-d' ou null se a entrada for null.
     */
    public static function dateTimeToDate(string|null $dateTimeString): string|null
    {
        if ($dateTimeString === null) {
            return null;
        }

        $dateTime = new \DateTime($dateTimeString);
        if (!$dateTime) {
            throw new \InvalidArgumentException("Invalid data: {$dateTimeString}");
        }
        return $dateTime->format('Y-m-d');
    }

    /**
     * Converte uma string de data para um timestamp (segundos desde a época Unix).
     *
     * @param string|null $dateString A string de data a ser convertida.
     * @return int|null O timestamp correspondente à data ou null se a entrada for null.
     */
    public static function dateToSeconds(string|null $dateString): int|null
    {
        if ($dateString === null) {
            return null;
        }
        $dateTime = new \DateTime($dateString);
        if (!$dateTime) {
            throw new \InvalidArgumentException("Invalid data: {$dateString}");
        }
        return $dateTime->getTimestamp();
    }

    /**
     * Converte segundos em uma string de data no formato especificado.
     *
     * @param int|null $seconds O número de segundos a ser convertido.
     * @param string $format O formato da data (padrão: 'Y-m-d H:i:s').
     * @return string|null A string de data formatada ou null se o timestamp for negativo.
     */
    public static function secondsToDate(int|null $seconds, string $format = 'Y-m-d H:i:s'): string|null
    {
        if ($seconds === null) {
            return null;
        }

        if ($seconds < 0) {
            throw new \InvalidArgumentException("Invalid seconds: {$seconds}");
        }
        return date($format, $seconds);
    }

    /**
     * Normaliza uma string de data para o formato padrão ISO (Y-m-d).
     * * Este método é útil para converter datas recebidas de formulários ou inputs
     * (ex: 12/31/2024) para o formato padrão de armazenamento em banco de dados.
     *
     * @param string|null $dateString A string de data a ser convertida.
     * @param string $format O formato esperado da string de entrada (padrão: 'm/d/Y').
     * @return string|null A data formatada como 'Y-m-d' ou null se a entrada for null.
     * @throws \InvalidArgumentException Se a data fornecida não corresponder ao formato especificado.
     */
    public static function normalizeDate(string|null $dateString, string $format = "m/d/Y"): string|null
    {
        if ($dateString === null) {
            return null;
        }
        $date = \DateTime::createFromFormat($format, $dateString);
        if ($date === false) {
            throw new \InvalidArgumentException("Invalid data: {$dateString}");
        }
        return $date->format('Y-m-d');
    }

    /**
     * Converte uma string de data e hora para segundos desde a época Unix, considerando o fuso horário.
     *
     * @param string $dateString A string de data e hora a ser convertida.
     * @param \DateTimeZone|null $timezone O fuso horário a ser considerado (opcional).
     * @return int|null O timestamp correspondente à data e hora ou null se a entrada for null.
     */
    public static function dateToSecondsWithTimezone(string|null $dateString, ?\DateTimeZone $timezone = null): int|null
    {
        if ($dateString === null) {
            return null;
        }
        $dateTime = new \DateTime($dateString, $timezone ?: new \DateTimeZone(date_default_timezone_get()));
        return $dateTime->getTimestamp();
    }

    /**
     * Formata um valor monetário para o formato de moeda brasileiro (R$).
     *
     * @param float|null $value O valor a ser formatado.
     * @param string $currency A moeda (padrão: 'BRL').
     * @param string $locale O locale a ser usado (padrão: 'pt_BR').
     * @return string|null O valor formatado como moeda ou null se o valor for null.
     */
    public static function formatCurrency(float|null $value, string $currency = 'BRL', string $locale = 'pt_BR'): string|null
    {
        if ($value === null) {
            return null;
        }
        $fmt = new \NumberFormatter($locale, \NumberFormatter::CURRENCY);
        return $fmt->formatCurrency($value, $currency);
    }

    /**
     * Limita o tamanho de um texto e adiciona reticências se necessário.
     *
     * @param string|null $dado O texto a ser limitado.
     * @param int $qtdvisivel Quantidade de caracteres visíveis.
     * @param string $abbreviator O texto a ser adicionado no final se o texto for cortado.
     * @return string O texto limitado com reticências.
     */
    public static function limitText(string|null $text, int $length, string $abbreviator = "..."): string|null
    {
        if ($text === null) {
            return null;
        }
        $text = $text ?? '';
        if (mb_strlen($text) <= $length) {
            return $text;
        }
        return mb_substr($text, 0, $length) . $abbreviator;
    }

    /**
     * Converte uma string para CamelCase (ex: "user_id" -> "userId").
     * @param string|null $string A string a ser convertida.
     * @return string|null A string convertida para CamelCase ou null se a entrada for
     */
    public static function toCamelCase(string|null $string): string|null
    {
        if ($string === null) {
            return null;
        }
        return lcfirst(str_replace(' ', '', ucwords(str_replace(['_', '-'], ' ', $string))));
    }

    /**
     * Converte uma string para PascalCase (ex: "user_id" -> "UserId").
     * Útil para nomes de Classes.
     * @param string|null $string A string a ser convertida.
     * @return string|null A string convertida para PascalCase ou null se a entrada for null.
     */
    public static function toPascalCase(string|null $string): string|null
    {
        if ($string === null) {
            return null;
        }
        return str_replace(' ', '', ucwords(str_replace(['_', '-'], ' ', $string)));
    }

    /**
     * Converte uma string para SnakeCase (ex: "userId" -> "user_id").
     * Útil para nomes de colunas no banco.
     * @param string|null $string A string a ser convertida.
     * @return string|null A string convertida para SnakeCase ou null se a entrada for null.
     */
    public static function toSnakeCase(string|null $string): string|null
    {
        if ($string === null) {
            return null;
        }
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $string));
    }

    /**
     * Cria um Slug (URL amigável).
     * Ex: "Olá Mundo!" -> "ola-mundo"
     * @param string|null $string A string a ser convertida em slug.
     * @param string $separator O separador a ser usado no slug (padrão "-").
     * @return string|null O slug resultante ou null se a entrada for null.
     */
    public static function toSlug(string|null $string, string $separator = '-'): string|null
    {
        if ($string === null) {
            return null;
        }
        // Remove acentos
        $string = iconv('UTF-8', 'ASCII//TRANSLIT', $string);
        // Remove caracteres especiais e converte para minúsculo
        $string = preg_replace('/[^a-zA-Z0-9\s]/', '', strtolower($string));
        // Substitui espaços pelo separador
        return preg_replace('/\s+/', $separator, trim($string));
    }

    /**
     * Converte bytes para tamanho legível (KB, MB, GB).
     * Ex: 1024 -> "1 KB"
     * @param int|null $bytes O número de bytes a ser convertido.
     * @param int $precision A precisão decimal (padrão: 2).
     * @return string|null O tamanho convertido em formato legível ou null se a entrada for null.
     */
    public static function bytesToHuman(int|null $bytes, int $precision = 2): string|null
    {
        if ($bytes === null) {
            return null;
        }
        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, self::l_countStatic($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    /**
     * Remove todos os caracteres que não sejam números de uma string.
     *
     * @param string|null $input Texto de entrada para sanitização.
     * @return string|null Retorna apenas os números encontrados ou null caso a entrada seja null.
     */
    public static function toNumbersOnly(string|null $input): string|null
    {
        if ($input === null) {
            return null;
        }

        return preg_replace('/\D/', '', $input) ?? '';
    }

    /**
     * Converte uma string XML em array associativo.
     *
     * @param string|null $xmlString XML em formato string.
     * @return array|null Retorna o array convertido ou null caso a entrada seja null.
     *
     * @throws \InvalidArgumentException Quando o XML informado for inválido.
     */
    public static function xmlToArray(string|null $xmlString): array|null
    {
        if ($xmlString === null) {
            return null;
        }
        $xml = simplexml_load_string($xmlString, "SimpleXMLElement", LIBXML_NOCDATA);
        if ($xml === false) {
            throw new \InvalidArgumentException("XML invalid.");
        }
        return json_decode(json_encode($xml), true);
    }

    /**
     * Converte uma cor hexadecimal para o formato RGB.
     *
     * Aceita valores nos formatos #RGB, RGB, #RRGGBB ou RRGGBB.
     *
     * @param string|null $hex Cor em formato hexadecimal.
     * @return array|null Array associativo com chaves 'r', 'g' e 'b', ou null caso a entrada seja null.
     */
    public static function hexToRgb(string|null $hex): array|null
    {
        if ($hex === null) {
            return null;
        }

        $hex = ltrim($hex, '#');

        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }

        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        return ['r' => $r, 'g' => $g, 'b' => $b];
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
