<?php

declare(strict_types=1);

/**
 * @author Weleny Santos <welenysantos@gmail.com>
 * @package Lumynus\Framework
 */

namespace Lumynus\Framework;

use Lumynus\Framework\LumaClasses;
use Lumynus\Framework\Logs;
use Lumynus\Framework\Config;

class File extends LumaClasses
{

    /**
     * Lê o conteúdo de um arquivo no diretório de arquivos do projeto.
     * @param string $fileName O nome do arquivo a ser lido.
     * @param string $path O caminho relativo dentro do diretório de arquivos para ler o arquivo. (Opcional)
     * @return string|null Retorna o conteúdo do arquivo como uma string, ou null se ocorrer um erro.
     */
    public function read(string $fileName, string $path = ''): ?string
    {
        try {
            $pathAbsolute = $this->getPath($path);
            if (!file_exists($pathAbsolute . $fileName)) {
                Logs::register('File not found: ' . $fileName, 'error');
                return null;
            }
            return file_get_contents($pathAbsolute . $fileName);
        } catch (\Throwable $th) {
            Logs::register('File read error: ' . $th->getMessage(), 'error');
            return null;
        }
    }

    /**
     * Renomeia um arquivo no diretório de arquivos do projeto.
     * @param string $fileName O nome do arquivo a ser renomeado.
     * @param string $newName O novo nome que o arquivo deve ter.
     * @param string $path O caminho relativo dentro do diretório de arquivos para renomear o arquivo. (Opcional)
     * @return bool Retorna true se o arquivo foi renomeado com sucesso, caso contrário, retorna false.
     */
    public function replace(string $fileName, string $newName, string $path = ''): bool
    {
        try {
            $pathAbsolute = $this->getPath($path);

            $oldFile = $pathAbsolute . $fileName;

            if (!is_file($oldFile)) {
                Logs::register('File not found: ' . $fileName, 'error');
                return false;
            }

            if (!is_dir($pathAbsolute) && !mkdir($pathAbsolute, 0755, true)) {
                Logs::register('Failed to create directory: ' . $pathAbsolute, 'error');
                return false;
            }
            return rename($pathAbsolute . $fileName, $pathAbsolute . $newName);
        } catch (\Throwable $th) {
            Logs::register('File rename error: ' . $th->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Verifica se um arquivo existe no diretório de arquivos do projeto.
     * @param string $fileName O nome do arquivo a ser verificado.
     * @param string $path O caminho relativo dentro do diretório de arquivos para verificar o arquivo. (Opcional)
     * @return bool Retorna true se o arquivo existir, caso contrário, retorna false.
     */
    public function exists(string $fileName, string $path = ''): bool
    {
        try {
            $pathAbsolute = $this->getPath($path);
            return file_exists($pathAbsolute . $fileName);
        } catch (\Throwable $th) {
            Logs::register('File exists error: ' . $th->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Exclui um arquivo do diretório de arquivos do projeto.
     * @param string $fileName O nome do arquivo a ser excluído.
     * @param string $path O caminho relativo dentro do diretório de arquivos para excluir o arquivo. (Opcional)
     * @return bool Retorna true se o arquivo foi excluído com sucesso, caso contrário, retorna false.
     */
    public function delete(string $fileName, string $path = ''): bool
    {
        try {
            $pathAbsolute = $this->getPath($path);
            $file = $pathAbsolute . $fileName;

            if (!is_file($file)) {
                Logs::register('File not found: ' . $fileName, 'error');
                return false;
            }

            return unlink($file);
        } catch (\Throwable $th) {
            Logs::register('File delete error: ' . $th->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Salva um arquivo enviado para o diretório de arquivos do projeto.
     * @param string $from O caminho temporário do arquivo enviado. @example: $file['file']['tmp_name']
     * @param string $newName O novo nome que o arquivo deve ter ao ser salvo.
     * @param string $path O caminho relativo dentro do diretório de arquivos para salvar o arquivo. (Opcional)
     * @return bool Retorna true se o arquivo foi salvo com sucesso, caso contrário, retorna false.
     */
    public function save(string $from, string $newName, string $path = ''): bool
    {
        try {
            $pathAbsolute = $this->getPath($path);
            if (!is_dir($pathAbsolute) && !mkdir($pathAbsolute, 0755, true)) {
                Logs::register('Failed to create directory: ' . $pathAbsolute, 'error');
                return false;
            }
            return move_uploaded_file($from, $pathAbsolute . $newName);
        } catch (\Throwable $th) {
            Logs::register('File upload error: ' . $th->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Copia um arquivo no diretório de arquivos do projeto.
     * @param string $fileName O nome do arquivo a ser copiado.
     * @param string $newName O novo nome que o arquivo copiado deve ter.
     * @param string $path O caminho relativo dentro do diretório de arquivos para copiar o arquivo. (Opcional)
     * @return bool Retorna true se o arquivo foi copiado com sucesso, caso contrário, retorna false.
     */
    public function copy(string $fileName, string $newName, string $path = ''): bool
    {
        try {
            $pathAbsolute = $this->getPath($path);
            if (!is_dir($pathAbsolute) && !mkdir($pathAbsolute, 0755, true)) {
                Logs::register('Failed to create directory: ' . $pathAbsolute, 'error');
                return false;
            }
            return copy($pathAbsolute . $fileName, $pathAbsolute . $newName);
        } catch (\Throwable $th) {
            Logs::register('File copy error: ' . $th->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Busca arquivos no diretório de arquivos do projeto, filtrando pelo nome do arquivo se uma chave for fornecida.
     * @param string $key A chave de pesquisa para filtrar os arquivos pelo nome.
     * @param string $path O caminho relativo dentro do diretório de arquivos para buscar os arquivos. (Opcional)
     * @return array
     */
    public function search(string $key = '', string $path = ''): array
    {
        $pathAbsolute = $this->getPath($path);

        if (!is_dir($pathAbsolute)) {
            return [];
        }

        $files = array_diff(
            scandir($pathAbsolute, SCANDIR_SORT_DESCENDING),
            ['.', '..']
        );

        $key = Sanitizer::string($key);
        if (!empty($key)) {
            $files = array_filter($files, function ($file) use ($key) {
                return str_contains($file, $key);
            });
        }

        return $files;
    }

    /**
     * Obtém o caminho absoluto para o diretório de arquivos do projeto, com base no caminho relativo fornecido.
     * @param string $path O caminho relativo dentro do diretório de arquivos. (Opcional)
     * @return string O caminho absoluto para o diretório de arquivos.
     */
    public function getPath(string $path = ''): string
    {
        $pathProject = Config::pathProject();
        $pathFiles = Config::getApplicationConfig()['path']['files'];

        return rtrim($pathProject . $pathFiles, DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR
            . ($path !== ''
                ? trim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR
                : '');
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
