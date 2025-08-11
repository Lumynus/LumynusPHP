<?php

declare(strict_types=1);

namespace Lumynus\Bundle\Framework;

use Lumynus\Bundle\Framework\LumaClasses;
use Lumynus\Bundle\Framework\Config;

class Encryption extends LumaClasses
{
    /**
     * Criptografa um conteúdo com AES-256-CBC
     *
     * @param mixed $data Conteúdo a criptografar
     * @param string|null $keyName Nome do arquivo da chave PEM (opcional)
     * @return string Texto criptografado codificado em base64
     * @throws \RuntimeException Se o OpenSSL não estiver disponível ou a chave não for válida
     */
    public static function encrypt($data, ?string $keyName = null): string
    {
        self::verificaExtensaoOpenSSL();
        $chave = self::obterChave($keyName);
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
        $textCrypt = openssl_encrypt($data, 'aes-256-cbc', $chave, 0, $iv);
        if ($textCrypt === false) {
            throw new \RuntimeException("Error encrypting data..");
        }
        return base64_encode($iv . $textCrypt);
    }

    /**
     * Descriptografa um conteúdo com AES-256-CBC
     *
     * @param string $data Texto criptografado em base64
     * @param string|null $keyName Nome do arquivo da chave PEM (opcional)
     * @return string Texto descriptografado
     * @throws \RuntimeException Se o OpenSSL não estiver disponível ou falhar a descriptografia
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
     * Gera uma nova chave aleatória de 32 bytes e salva em um arquivo .pem
     *
     * @param string|null $keyName Nome do arquivo da chave (opcional)
     * @throws \RuntimeException Se não for possível criar ou salvar a chave
     * @return string Caminho completo da chave gerada
     */
    public static function createKey(?string $keyName = null): string
    {
        $keyDir = Config::pathProject() .
            DIRECTORY_SEPARATOR . 'vendor' .
            DIRECTORY_SEPARATOR . 'Lumynus' .
            DIRECTORY_SEPARATOR . 'Memory' .
            DIRECTORY_SEPARATOR . 'keys';

        if (!is_dir($keyDir)) {
            if (!mkdir($keyDir, 0755, true)) {
                throw new \RuntimeException("Failed to create key directory: {$keyDir}");
            }
        }

        $keyFile = $keyDir . DIRECTORY_SEPARATOR . ($keyName ?? 'key') . '.pem';

        $randomKey = random_bytes(32); // Garante 256 bits (32 bytes)
        if (file_put_contents($keyFile, $randomKey) === false) {
            throw new \RuntimeException("Failed to write key to file: {$keyFile}");
        }

        return $keyFile;
    }

    /**
     * Remove o arquivo da chave PEM especificada
     *
     * @param string|null $keyName Nome do arquivo da chave (opcional)
     * @return bool True se o arquivo foi removido ou não existia, false se falhou na remoção
     */
    public static function removeKey(?string $keyName = null): bool
    {
        $keyFile = Config::pathProject() .
            DIRECTORY_SEPARATOR . 'vendor' .
            DIRECTORY_SEPARATOR . 'Lumynus' .
            DIRECTORY_SEPARATOR . 'Memory' .
            DIRECTORY_SEPARATOR . 'keys' .
            DIRECTORY_SEPARATOR . ($keyName ?? 'key') . '.pem';

        if (!file_exists($keyFile)) {
            // Arquivo já não existe, considera sucesso
            return true;
        }

        // Tenta apagar o arquivo
        return @unlink($keyFile);
    }

    /**
     * Salva um valor serializado e criptografado em arquivo .luma
     *
     * @param string $nameFile Nome do arquivo (sem extensão)
     * @param mixed $value Valor a ser salvo
     * @param string|null $keyName Nome da chave
     * @throws \RuntimeException Se falhar ao criar pasta ou salvar arquivo
     * @return string Caminho completo do arquivo salvo
     */
    public static function saveToFile(string $nameFile, $value, ?string $keyName = null): bool
    {
        $dir = Config::pathProject() .
            DIRECTORY_SEPARATOR . 'vendor' .
            DIRECTORY_SEPARATOR . 'Lumynus' .
            DIRECTORY_SEPARATOR . 'Memory' .
            DIRECTORY_SEPARATOR . 'encryptions';

        if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
            return false;
        }

        $filePath = $dir . DIRECTORY_SEPARATOR . $nameFile . '.luma';

        $serialized = serialize($value);

        try {
            $encrypted = self::Encrypt($serialized, $keyName);
        } catch (\RuntimeException $e) {
            return false;
        }

        return file_put_contents($filePath, $encrypted) !== false;
    }

    /**
     * Remove o arquivo .luma especificado
     *
     * @param string $nameFile Nome do arquivo (sem extensão)
     * @return bool True se arquivo removido ou não existia, false se falhou na remoção
     */
    public static function removeToFile(string $nameFile): bool
    {
        $filePath = Config::pathProject() .
            DIRECTORY_SEPARATOR . 'vendor' .
            DIRECTORY_SEPARATOR . 'Lumynus' .
            DIRECTORY_SEPARATOR . 'Memory' .
            DIRECTORY_SEPARATOR . 'encryptions' .
            DIRECTORY_SEPARATOR . $nameFile . '.luma';

        if (!file_exists($filePath)) {
            return true; // arquivo não existe, considera sucesso
        }

        return @unlink($filePath);
    }

    /**
     * Lê um ou vários arquivos .luma, descriptografa e desserializa os valores salvos
     *
     * @param string|array $nameFile Nome do arquivo (sem extensão) ou array de nomes
     * @param string|null $keyName Nome da chave para descriptografar (opcional)
     * @throws \RuntimeException Se algum arquivo não existir ou falhar a descriptografia/desserialização
     * @return mixed Valor original salvo (para 1 arquivo) ou array associativo [nomeArquivo => valor] para vários arquivos
     */
    public static function readFiles(string|array $nameFile, ?string $keyName = null)
    {
        // Normaliza para array (se for string, transforma em array com 1 elemento)
        $files = is_array($nameFile) ? $nameFile : [$nameFile];
        $results = [];

        foreach ($files as $file) {
            $filePath = Config::pathProject() .
                DIRECTORY_SEPARATOR . 'vendor' .
                DIRECTORY_SEPARATOR . 'Lumynus' .
                DIRECTORY_SEPARATOR . 'Memory' .
                DIRECTORY_SEPARATOR . 'encryptions' .
                DIRECTORY_SEPARATOR . $file . '.luma';

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

        // Se só tinha um arquivo, retorna só o valor direto
        if (count($results) === 1) {
            return array_shift($results);
        }

        // Senão retorna o array associativo
        return $results;
    }


    /**
     * Verifica se a extensão OpenSSL está habilitada
     *
     * @throws \RuntimeException Se a extensão não estiver disponível
     */
    private static function verificaExtensaoOpenSSL(): void
    {
        if (!extension_loaded('openssl')) {
            throw new \RuntimeException("The OpenSSL extension is not enabled in PHP.");
        }
    }

    /**
     * Lê a chave do arquivo e retorna os primeiros 32 bytes (para AES-256)
     *
     * @param string|null $keyName Nome do arquivo da chave PEM (opcional)
     * @return string Chave de 32 bytes
     * @throws \RuntimeException Se o arquivo não for encontrado ou inválido
     */
    private static function obterChave(?string $keyName): string
    {
        $caminho = Config::pathProject() .
            DIRECTORY_SEPARATOR .
            'vendor' .  DIRECTORY_SEPARATOR .
            'Lumynus' .  DIRECTORY_SEPARATOR .
            'Memory' .  DIRECTORY_SEPARATOR .
            'keys' .
            DIRECTORY_SEPARATOR .
            ($keyName ?? 'key') . '.pem';

        if (!file_exists($caminho)) {
            throw new \RuntimeException("Key file not found in: {$caminho}");
        }

        $keyContent = file_get_contents($caminho);
        $chave = substr($keyContent, 0, 32);

        if (strlen($chave) !== 32) {
            throw new \RuntimeException("The key must be at least 32 bytes long.");
        }

        return $chave;
    }
}