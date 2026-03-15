<?php

declare(strict_types=1);

/**
 * @author Weleny Santos <welenysantos@gmail.com>
 * @package Lumynus\Framework
 */

namespace Lumynus\Framework;

use Lumynus\Framework\LumaClasses;
use Lumynus\Framework\Config;

final class Encryption extends LumaClasses
{
    /**
     * Criptografa um conteúdo usando AES-256-GCM.
     *
     * @param string|null $data Conteúdo a ser criptografado
     * @param string|null $keyName Nome da chave PEM a ser utilizada
     *
     * @return string Conteúdo criptografado em Base64
     *
     * @throws \InvalidArgumentException Se o conteúdo for vazio ou nulo
     * @throws \RuntimeException Se OpenSSL não estiver disponível ou falhar
     */
    public static function encrypt(string|null $data, ?string $keyName = null): string
    {
        if ($data === null || $data === '') {
            throw new \InvalidArgumentException('Data to encrypt cannot be null or empty.');
        }

        self::verificaExtensaoOpenSSL();

        if (!in_array('aes-256-gcm', openssl_get_cipher_methods(), true)) {
            throw new \RuntimeException('AES-256-GCM not supported on this server.');
        }

        $key = self::obterChave($keyName);

        $iv = random_bytes(12);
        $tag = '';

        $ciphertext = openssl_encrypt(
            $data,
            'aes-256-gcm',
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        if ($ciphertext === false) {
            throw new \RuntimeException('Error encrypting data.');
        }

        return base64_encode($iv . $tag . $ciphertext);
    }


    /**
     * Descriptografa um conteúdo criptografado com AES-256-GCM.
     *
     * @param string|null $data Conteúdo criptografado em Base64
     * @param string|null $keyName Nome da chave PEM a ser utilizada
     *
     * @return string Conteúdo descriptografado
     *
     * @throws \InvalidArgumentException Se o conteúdo for vazio ou nulo
     * @throws \RuntimeException Se a descriptografia falhar
     */
    public static function decrypt(string|null $data, ?string $keyName = null): string
    {
        if ($data === null || $data === '') {
            throw new \InvalidArgumentException('Data to decrypt cannot be null or empty.');
        }

        self::verificaExtensaoOpenSSL();

        $raw = base64_decode($data, true);
        if ($raw === false) {
            throw new \RuntimeException('Invalid base64 data.');
        }

        $ivLength  = 12;
        $tagLength = 16;

        $iv  = substr($raw, 0, $ivLength);
        $tag = substr($raw, $ivLength, $tagLength);
        $ciphertext = substr($raw, $ivLength + $tagLength);

        $key = self::obterChave($keyName);

        $plaintext = openssl_decrypt(
            $ciphertext,
            'aes-256-gcm',
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        if ($plaintext === false) {
            throw new \RuntimeException('Error decrypting data.');
        }

        return $plaintext;
    }


    /**
     * Cria uma nova chave AES de 32 bytes e salva em arquivo PEM.
     *
     * @param string|null $keyName Nome da chave (sem extensão)
     *
     * @return string Caminho completo do arquivo da chave criada
     *
     * @throws \RuntimeException Se o diretório ou arquivo não puder ser criado
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


        $tmpFile = $keyFile . '.tmp';

        if (file_put_contents($tmpFile, $randomKey, LOCK_EX) === false) {
            throw new \RuntimeException("Failed to write temp key file: {$tmpFile}");
        }

        if (!rename($tmpFile, $keyFile)) {
            @unlink($tmpFile);
            throw new \RuntimeException("Failed to move temp key file to final location: {$keyFile}");
        }
        if (!chmod($keyFile, 0600)) {
            throw new \RuntimeException("Failed to set permissions on key file: {$keyFile}");
        }
        return $keyFile;
    }

    /**
     * Remove uma chave PEM existente.
     *
     * @param string|null $keyName Nome da chave (sem extensão)
     *
     * @return bool True se removida ou se não existir
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
     * Serializa e criptografa um valor, salvando em arquivo .luma.
     *
     * @param string $nameFile Nome do arquivo (sem extensão)
     * @param mixed $value Valor a ser serializado e criptografado
     * @param string|null $keyName Nome da chave PEM
     *
     * @return bool True em caso de sucesso
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

        $tmp = $filePath . '.tmp';

        if (file_put_contents($tmp, $encrypted, LOCK_EX) === false) {
            return false;
        }

        return rename($tmp, $filePath);
    }

    /**
     * Remove um arquivo criptografado (.luma).
     *
     * @param string $nameFile Nome do arquivo (sem extensão)
     *
     * @return bool True se removido ou se não existir
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
     * Lê, descriptografa e desserializa arquivos .luma.
     *
     * @param string|array $nameFile Nome do arquivo ou lista de arquivos
     * @param string|null $keyName Nome da chave PEM
     *
     * @return mixed|array Retorna o valor desserializado ou array de valores
     *
     * @throws \RuntimeException Se o arquivo não existir ou falhar
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

            $value = @unserialize($decrypted, ['allowed_classes' => false]);
            if ($value === false && $decrypted !== serialize(false)) {
                throw new \RuntimeException("Failed to unserialize data from file: {$filePath}");
            }

            $results[$file] = $value;
        }

        return self::l_countStatic($results) === 1 ? array_shift($results) : $results;
    }

    /**
     * Verifica se a extensão OpenSSL está habilitada.
     *
     * @throws \RuntimeException Se OpenSSL não estiver disponível
     */
    private static function verificaExtensaoOpenSSL(): void
    {
        if (!extension_loaded('openssl')) {
            throw new \RuntimeException("The OpenSSL extension is not enabled in PHP.");
        }
    }

    /**
     * Obtém a chave AES (32 bytes) de um arquivo PEM.
     *
     * @param string|null $keyName Nome da chave
     *
     * @return string Chave AES de 32 bytes
     *
     * @throws \RuntimeException Se a chave não existir ou for inválida
     */
    private static function obterChave(?string $keyName): string
    {
        $keyName = self::sanitizeFileName($keyName ?? 'key');

        if (str_contains($keyName, '.pem')) {
            $keyName = str_ireplace('.pem', '', $keyName);
        }

        $caminho = Config::pathProject() .
            DIRECTORY_SEPARATOR . 'storage' .
            DIRECTORY_SEPARATOR . 'keys' .
            DIRECTORY_SEPARATOR .
            $keyName . '.pem';

        if (!file_exists($caminho)) {
            throw new \RuntimeException("Key file not found: {$caminho}");
        }

        $keyContent = file_get_contents($caminho);
        if ($keyContent === false) {
            throw new \RuntimeException("Failed to read key file.");
        }
        $chave = substr($keyContent, 0, 32);

        if (strlen($chave) !== 32) {
            throw new \RuntimeException("The key must be at least 32 bytes long.");
        }

        return $chave;
    }

    /**
     * Sanitiza nomes de arquivos para evitar Path Traversal.
     *
     * @param string $name Nome original do arquivo
     *
     * @return string Nome sanitizado
     *
     * @throws \InvalidArgumentException Se o nome for inválido
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
