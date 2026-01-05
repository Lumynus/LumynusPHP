<?php

declare(strict_types=1);

namespace Lumynus\Bundle\Framework;

use Lumynus\Bundle\Framework\LumaClasses;
use Lumynus\Bundle\Framework\Config;

final class Encryption extends LumaClasses
{
    /**
     * Criptografa um conteúdo com AES-256-CBC
     */
    public static function encrypt($data, ?string $keyName = null): string
    {
        self::verificaExtensaoOpenSSL();
        $chave = self::obterChave($keyName);
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
        $textCrypt = openssl_encrypt($data, 'aes-256-cbc', $chave, 0, $iv);
        if ($textCrypt === false) {
            throw new \RuntimeException("Error encrypting data.");
        }
        return base64_encode($iv . $textCrypt);
    }

    /**
     * Descriptografa um conteúdo com AES-256-CBC
     */
    public static function decrypt(string $data, ?string $keyName = null): string
    {
        self::verificaExtensaoOpenSSL();
        $data = base64_decode($data);
        $ivLength = openssl_cipher_iv_length('aes-256-cbc');
        $iv = substr($data, 0, $ivLength);
        $textCrypt = substr($data, $ivLength);
        $chave = self::obterChave($keyName);
        $texto = openssl_decrypt($textCrypt, 'aes-256-cbc', $chave, 0, $iv);
        if ($texto === false) {
            throw new \RuntimeException("Error decrypting data.");
        }
        return $texto;
    }

    /**
     * Cria uma nova chave AES de 32 bytes e salva em arquivo .pem
     */
    public static function createKey(?string $keyName = null): string
    {
        $keyDir = Config::pathProject() .
            DIRECTORY_SEPARATOR . 'storage'
            . DIRECTORY_SEPARATOR . 'keys';

        if (!is_dir($keyDir) && !mkdir($keyDir, 0755, true)) {
            throw new \RuntimeException("Failed to create key directory: {$keyDir}");
        }

        $keyName = self::sanitizeFileName($keyName ?? 'key');
        $keyFile = $keyDir . DIRECTORY_SEPARATOR . $keyName . '.pem';

        $randomKey = random_bytes(32);
        if (file_put_contents($keyFile, $randomKey) === false) {
            throw new \RuntimeException("Failed to write key to file: {$keyFile}");
        }

        return $keyFile;
    }

    /**
     * Remove chave PEM
     */
    public static function removeKey(?string $keyName = null): bool
    {
        $keyName = self::sanitizeFileName($keyName ?? 'key');

        $keyFile = Config::pathProject() .
            DIRECTORY_SEPARATOR . 'storage' .
            DIRECTORY_SEPARATOR . 'keys' .
            DIRECTORY_SEPARATOR . $keyName . '.pem';

        if (!file_exists($keyFile)) return true;

        return @unlink($keyFile);
    }

    /**
     * Salva valor serializado e criptografado em arquivo .luma
     */
    public static function saveToFile(string $nameFile, $value, ?string $keyName = null): bool
    {
        $dir = Config::pathProject() .
            DIRECTORY_SEPARATOR . 'storage' .
            DIRECTORY_SEPARATOR . 'encryptions';

        if (!is_dir($dir) && !mkdir($dir, 0755, true)) return false;

        $nameFile = self::sanitizeFileName($nameFile);
        $filePath = $dir . DIRECTORY_SEPARATOR . $nameFile . '.luma';

        $serialized = serialize($value);
        try {
            $encrypted = self::encrypt($serialized, $keyName);
        } catch (\RuntimeException $e) {
            return false;
        }

        return file_put_contents($filePath, $encrypted) !== false;
    }

    /**
     * Remove arquivo .luma
     */
    public static function removeToFile(string $nameFile): bool
    {
        $dir = Config::pathProject() .
            DIRECTORY_SEPARATOR . 'storage' .
            DIRECTORY_SEPARATOR . 'encryptions';

        $nameFile = self::sanitizeFileName($nameFile);
        $filePath = $dir . DIRECTORY_SEPARATOR . $nameFile . '.luma';

        if (!file_exists($filePath)) return true;

        return @unlink($filePath);
    }

    /**
     * Lê arquivos .luma, descriptografa e desserializa
     */
    public static function readFiles(string|array $nameFile, ?string $keyName = null)
    {
        $dir = Config::pathProject() .
            DIRECTORY_SEPARATOR . 'storage' .
            DIRECTORY_SEPARATOR . 'encryptions';

        $files = is_array($nameFile) ? $nameFile : [$nameFile];
        $results = [];

        foreach ($files as $file) {
            $file = self::sanitizeFileName($file);
            $filePath = $dir . DIRECTORY_SEPARATOR . $file . '.luma';

            if (!file_exists($filePath)) {
                throw new \RuntimeException("File not found: {$filePath}");
            }

            $encrypted = file_get_contents($filePath);
            if ($encrypted === false) {
                throw new \RuntimeException("Failed to read file: {$filePath}");
            }

            $decrypted = self::decrypt($encrypted, $keyName);

            $value = @unserialize($decrypted);
            if ($value === false && $decrypted !== serialize(false)) {
                throw new \RuntimeException("Failed to unserialize data from file: {$filePath}");
            }

            $results[$file] = $value;
        }

        return count($results) === 1 ? array_shift($results) : $results;
    }

    /**
     * Verifica extensão OpenSSL
     */
    private static function verificaExtensaoOpenSSL(): void
    {
        if (!extension_loaded('openssl')) {
            throw new \RuntimeException("The OpenSSL extension is not enabled in PHP.");
        }
    }

    /**
     * Lê chave do arquivo PEM (32 bytes)
     */
    private static function obterChave(?string $keyName): string
    {
        $keyName = self::sanitizeFileName($keyName ?? 'key');

        $caminho = Config::pathProject() .
            DIRECTORY_SEPARATOR . 'storage' .
            DIRECTORY_SEPARATOR . 'keys' .
            DIRECTORY_SEPARATOR .
            $keyName . '.pem';

        if (!file_exists($caminho)) {
            throw new \RuntimeException("Key file not found: {$caminho}");
        }

        $keyContent = file_get_contents($caminho);
        $chave = substr($keyContent, 0, 32);

        if (strlen($chave) !== 32) {
            throw new \RuntimeException("The key must be at least 32 bytes long.");
        }

        return $chave;
    }

    /**
     * Proteção contra Path Traversal
     */
    private static function sanitizeFileName(string $name): string
    {
        $filename = basename($name);
        $filename = preg_replace('/[^a-zA-Z0-9_\-]/', '', $filename);
        if (empty($filename)) {
            throw new \InvalidArgumentException("Invalid file name provided.");
        }
        return $filename;
    }
}
