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
     * @throws \InvalidArgumentException Se o JSON for inválido.
     */
    public static function jsonToArray(string $json): array
    {
        $data = json_decode($json, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \InvalidArgumentException('JSON invalid: ' . json_last_error_msg());
        }
        return $data;
    }

    /**
     * Converte um JSON em um objeto (stdClass).
     *
     * @param string $json A string JSON a ser convertida.
     * @return object O objeto resultante.
     * @throws \InvalidArgumentException Se o JSON for inválido.
     */
    public static function jsonToObject(string $json): object
    {
        $data = json_decode($json);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \InvalidArgumentException('JSON invalid: ' . json_last_error_msg());
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
            throw new \InvalidArgumentException("Data inválida: {$dateTimeString}");
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
            throw new \InvalidArgumentException("Data inválida: {$dateString}");
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
            throw new \InvalidArgumentException("Segundos inválidos: {$seconds}");
        }
        return date($format, $seconds);
    }

    public static function normalizeDate(string $dateString, string $format = "m/d/Y")
    {
        $date = \DateTime::createFromFormat($format, $dateString);
        if ($date === false) {
            throw new \InvalidArgumentException("Data inválida: {$dateString}");
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
