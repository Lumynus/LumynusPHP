<?php

declare(strict_types=1);

namespace Lumynus\Bundle\Framework;

use Lumynus\Bundle\Framework\LumaClasses;
use Lumynus\Bundle\Framework\Logs;

final class Regex extends LumaClasses
{
    /**
     * Testa se um valor corresponde a uma expressão regular.
     *
     * @param string $regex A expressão regular (ex: Requirements::EMAIL).
     * @param mixed $value O valor a ser testado.
     * @return bool
     */
    public static function test(string $regex, mixed $value = ""): bool
    {
        if (!is_string($value) && !is_numeric($value)) {
            return false;
        }

        try {
            return preg_match($regex, (string) $value) === 1;
        } catch (\Throwable $e) {
            Logs::register(
                'Invalid regex pattern',
                sprintf(
                    'Pattern: %s | Value: %s | Error: %s',
                    $regex,
                    is_scalar($value) ? (string) $value : gettype($value),
                    $e->getMessage()
                )
            );

            return false;
        }
    }

    /**
     * Refina uma string mantendo APENAS os caracteres permitidos na Regex.
     * * ATENÇÃO: Este método só funciona corretamente para Regex baseadas em 
     * listas de caracteres (ex: [A-Za-z], \d, \s). 
     * NÃO USE para refinar E-mails, URLs ou Datas.
     *
     * @param string $regex A regex que define o que é PERMITIDO (ex: Requirements::TEXT_ONLY).
     * @param string $value O valor a ser limpo.
     * @return string
     */
    public static function refine(string $regex, string $value = ""): string
    {
        if ($value === "") {
            return "";
        }

        try {
            $allowedChars = self::extractCharacterClass($regex);

            if ($allowedChars === null) {
                return $value;
            }
            $cleaningRegex = '/[^' . $allowedChars . ']+/u';

            return preg_replace($cleaningRegex, '', $value) ?? "";
        } catch (\Throwable $e) {
            Logs::register(
                'Invalid regex',
                'Error: ' . $e->getMessage()
            );

            return $value;
        }
    }

    /**
     * Tenta extrair a lista de caracteres permitidos de uma Regex.
     * * @param string $regex
     * @return string|null
     */
    private static function extractCharacterClass(string $regex): ?string
    {
        // Remove delimitadores comuns (#, /, ~) e modificadores do final
        $pattern = preg_replace('/^([\/#~])|([\/#~][a-z]*)$/i', '', $regex);

        // Remove âncoras de início/fim se existirem (^, $)
        $pattern = trim($pattern, '^$');

        // Remove quantificadores (+, *, ?, {n,m})
        $pattern = preg_replace('/[\+\*\?]+$/', '', $pattern);
        $pattern = preg_replace('/\{\d+(,\d*)?\}$/', '', $pattern);

        // Caso 1: A regex é explicitamente uma classe, ex: [A-Z0-9]
        // Pegamos o conteúdo de dentro do primeiro par de colchetes
        if (preg_match('/^\[(.*)\]$/', $pattern, $matches)) {
            return $matches[1];
        }

        // Caso 2: A regex usa atalhos sem colchetes, ex: \d+
        // Tentamos mapear atalhos comuns para seus equivalentes em regex
        $allowed = "";
        $found = false;

        if (str_contains($pattern, '\d')) {
            $allowed .= '0-9';
            $found = true;
        }
        if (str_contains($pattern, '\w')) {
            $allowed .= 'A-Za-z0-9_';
            $found = true;
        }
        if (str_contains($pattern, '\s')) {
            $allowed .= '\s';
            $found = true;
        }

        // Se achou algum atalho, retorna a composição
        if ($found) {
            return $allowed;
        }

        // Se a regex for complexa (grupos, pipes, lookaheads), aborta.
        // Ex: (abc|def) ou (?=.*)
        return null;
    }
}
