<?php

declare(strict_types=1);

namespace Lumynus\Bundle\Framework;

use Lumynus\Bundle\Framework\LumaClasses;

final class Converts extends LumaClasses
{

    /**
     * Converte uma string para um inteiro.
     *
     * @param string $input A string a ser convertida.
     * @return int O valor convertido em inteiro.
     */
    public static function toInt(string $input): int
    {
        return (int) $input;
    }

    /**
     * Converte uma string para um float.
     *
     * @param string $input A string a ser convertida.
     * @return float O valor convertido em float.
     */
    public static function toFloat(string $input): float
    {
        return (float) $input;
    }

    /**
     * Converte um valor para uma string.
     *
     * @param int|float|string|bool $input O valor a ser convertido.
     * @return string O valor convertido em string.
     */
    public static function toString(int|float|string|bool $input): string
    {
        return (string) $input;
    }

    /**
     * Converte um valor para um booleano.
     *
     * @param string|int $input O valor a ser convertido.
     * @return bool O valor convertido em booleano.
     */
    public static function toBoolean(string|int $input): bool
    {
        return filter_var($input, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false;
    }

    /**
     * Converte um objeto ou array em array associativo recursivamente.
     *
     * @param object|array $input Objeto ou array de entrada.
     * @return array Array resultante.
     */
    public static function toArray(object|array $input): array
    {
        return json_decode(json_encode($input), true);
    }

    /**
     * Converte um array ou objeto em um objeto (stdClass) recursivamente.
     *
     * @param array|object $input Array ou objeto de entrada.
     * @return object Objeto resultante.
     */
    public static function toObject(array|object $input): object
    {
        return json_decode(json_encode($input));
    }

    /**
     * Converte um JSON em um array associativo.
     *
     * @param string $json A string JSON a ser convertida.
     * @return array O array resultante.
     * @throws \InvalidArgumentException Se o JSON for invalid.
     */
    public static function jsonToArray(string $json): array
    {
        $data = json_decode($json, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \InvalidArgumentException('Invalid JSON: ' . json_last_error_msg());
        }
        return $data;
    }

    /**
     * Converte um JSON em um objeto (stdClass).
     *
     * @param string $json A string JSON a ser convertida.
     * @return object O objeto resultante.
     * @throws \InvalidArgumentException Se o JSON for invalid.
     */
    public static function jsonToObject(string $json): object
    {
        $data = json_decode($json);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \InvalidArgumentException('Invalid JSON: ' . json_last_error_msg());
        }
        return $data;
    }

    /**
     * Converte uma string de data e hora para uma string de data no formato 'Y-m-d'.
     *
     * @param string $dateTimeString A string de data e hora a ser convertida.
     * @return string A string de data no formato 'Y-m-d'.
     */
    public static function dateTimeToDate(string $dateTimeString): string
    {
        $dateTime = new \DateTime($dateTimeString);
        if (!$dateTime) {
            throw new \InvalidArgumentException("Invalid data: {$dateTimeString}");
        }
        return $dateTime->format('Y-m-d');
    }

    /**
     * Converte uma string de data para um timestamp (segundos desde a época Unix).
     *
     * @param string $dateString A string de data a ser convertida.
     * @return int O timestamp correspondente à data.
     */
    public static function dateToSeconds(string $dateString): int
    {
        $dateTime = new \DateTime($dateString);
        if (!$dateTime) {
            throw new \InvalidArgumentException("Invalid data: {$dateString}");
        }
        return $dateTime->getTimestamp();
    }

    /**
     * Converte segundos em uma string de data no formato especificado.
     *
     * @param int $seconds O número de segundos a ser convertido.
     * @param string $format O formato da data (padrão: 'Y-m-d H:i:s').
     * @return string A string de data formatada.
     */
    public static function secondsToDate(int $seconds, string $format = 'Y-m-d H:i:s'): string
    {
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
     * @param string $dateString A string de data a ser convertida.
     * @param string $format O formato esperado da string de entrada (padrão: 'm/d/Y').
     * @return string A data formatada como 'Y-m-d'.
     * @throws \InvalidArgumentException Se a data fornecida não corresponder ao formato especificado.
     */
    public static function normalizeDate(string $dateString, string $format = "m/d/Y")
    {
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
     * @return int O timestamp correspondente à data e hora.
     */
    public static function dateToSecondsWithTimezone(string $dateString, ?\DateTimeZone $timezone = null): int
    {
        $dateTime = new \DateTime($dateString, $timezone ?: new \DateTimeZone(date_default_timezone_get()));
        return $dateTime->getTimestamp();
    }

    /**
     * Formata um valor monetário para o formato de moeda brasileiro (R$).
     *
     * @param float $value O valor a ser formatado.
     * @param string $currency A moeda (padrão: 'BRL').
     * @param string $locale O locale a ser usado (padrão: 'pt_BR').
     * @return string O valor formatado como moeda.
     */
    public static function formatCurrency(float $value, string $currency = 'BRL', string $locale = 'pt_BR'): string
    {
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
    public static function limitText(string|null $text, int $length, string $abbreviator = "..."): string
    {
        $text = $text ?? '';
        if (mb_strlen($text) <= $length) {
            return $text;
        }
        return mb_substr($text, 0, $length) . $abbreviator;
    }

    /**
     * Converte uma string para CamelCase (ex: "user_id" -> "userId").
     */
    public static function toCamelCase(string $string): string
    {
        return lcfirst(str_replace(' ', '', ucwords(str_replace(['_', '-'], ' ', $string))));
    }

    /**
     * Converte uma string para PascalCase (ex: "user_id" -> "UserId").
     * Útil para nomes de Classes.
     */
    public static function toPascalCase(string $string): string
    {
        return str_replace(' ', '', ucwords(str_replace(['_', '-'], ' ', $string)));
    }

    /**
     * Converte uma string para SnakeCase (ex: "userId" -> "user_id").
     * Útil para nomes de colunas no banco.
     */
    public static function toSnakeCase(string $string): string
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $string));
    }

    /**
     * Cria um Slug (URL amigável).
     * Ex: "Olá Mundo!" -> "ola-mundo"
     */
    public static function toSlug(string $string, string $separator = '-'): string
    {
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
     */
    public static function bytesToHuman(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    /**
     * Remove tudo que não for número.
     * Útil para limpar máscaras de CPF, CNPJ, Telefone antes de salvar no banco.
     * Ex: "(11) 9999-8888" -> "1199998888"
     */
    public static function toNumbersOnly(string $input): string
    {
        return preg_replace('/\D/', '', $input) ?? '';
    }

    /**
     * Converte XML string para Array.
     */
    public static function xmlToArray(string $xmlString): array
    {
        $xml = simplexml_load_string($xmlString, "SimpleXMLElement", LIBXML_NOCDATA);
        if ($xml === false) {
            throw new \InvalidArgumentException("XML invalid.");
        }
        return json_decode(json_encode($xml), true);
    }

    /**
     * Converte Hexadecimal para RGB.
     * Ex: "#FFFFFF" -> [255, 255, 255]
     */
    public static function hexToRgb(string $hex): array
    {
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
