<?php

declare(strict_types=1);

namespace Lumynus\Http;

use Lumynus\Http\Contracts\Request as RequestInterface;

final class Request implements RequestInterface
{

    private array $attributes = [];

    /**
     * Contructor
     */
    public function __construct(
        private string $method,
        private string $uri,
        private array $query,
        private array $post,
        private array $headers,
        private array $files,
        private array $server,
        private mixed $body
    ) {}

    /**
     * fromGlobals
     *
     * Cria uma instância de Request a partir das variáveis globais do PHP,
     * centralizando o acesso aos dados da requisição HTTP atual.
     */
    public static function fromGlobals(): self
    {
        return new self(
            $_SERVER['REQUEST_METHOD'] ?? 'GET',
            $_SERVER['REQUEST_URI'] ?? '/',
            $_GET,
            $_POST,
            self::headersFromGlobals(),
            $_FILES,
            $_SERVER,
            json_decode(file_get_contents('php://input'), true)
        );
    }

    /**
     * headersFromGlobals
     *
     * Extrai os cabeçalhos HTTP a partir da variável global $_SERVER,
     * normalizando os nomes para o formato padrão de headers.
     */
    private static function headersFromGlobals(): array
    {
        $headers = [];

        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $name = str_replace('_', '-', substr($key, 5));
                $headers[$name] = $value;
            }
        }

        return $headers;
    }

    /**
     * getMethod
     *
     * Retorna o método HTTP da requisição (GET, POST, PUT, DELETE, etc).
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * getUri
     *
     * Retorna a URI solicitada na requisição.
     */
    public function getUri(): string
    {
        return $this->uri;
    }

    /**
     * getQueryParams
     *
     * Retorna todos os parâmetros enviados via query string (GET).
     */
    public function getQueryParams(): array
    {
        return $this->query;
    }

    /**
     * getParsedBody
     *
     * Retorna o corpo da requisição já processado, normalmente vindo de JSON
     * ou outro formato suportado.
     */
    public function getParsedBody(): array|null
    {
        return $this->body;
    }

    /**
     * getHeaders
     *
     * Retorna todos os cabeçalhos HTTP da requisição.
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * get
     *
     * Obtém um valor específico da query string (GET),
     * retornando um valor padrão caso a chave não exista.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }

    /**
     * post
     *
     * Obtém um valor específico dos dados enviados via POST,
     * retornando um valor padrão caso a chave não exista.
     */
    public function post(string $key, mixed $default = null): mixed
    {
        return $this->post[$key] ?? $default;
    }

    /**
     * file
     *
     * Retorna um arquivo específico enviado na requisição HTTP,
     * geralmente disponível em formulários do tipo multipart/form-data.
     * Caso o arquivo não exista, retorna o valor padrão informado.
     */
    public function file(string $key, mixed $default = null): mixed
    {
        return $this->files[$key] ?? $default;
    }

    /**
     * files
     *
     * Retorna todos os arquivos enviados na requisição HTTP,
     * normalmente provenientes da variável global $_FILES.
     */
    public function files(): mixed
    {
        return $this->files ?? [];
    }

    /**
     * server
     *
     * Retorna os dados do ambiente do servidor relacionados à requisição,
     * normalmente provenientes da variável global $_SERVER.
     */
    public function server(): mixed
    {
        return $this->server ?? [];
    }

    /**
     * setAttribute
     *
     * Adiciona ou atualiza um atributo interno da requisição.
     * Usado principalmente por middlewares para armazenar contexto.
     */
    public function setAttribute(string $key, mixed $value): void
    {
        $this->attributes[$key] = $value;
    }

    /**
     * unsetAttribute
     *
     * Remove um atributo interno da requisição previamente definido,
     * normalmente utilizado por middlewares para limpar ou redefinir
     * informações de contexto.
     */
    public function unsetAttribute(string $key): void
    {
        unset($this->attributes[$key]);
    }

    /**
     * getAttribute
     *
     * Recupera um atributo interno da requisição definido por middlewares
     * ou outras camadas do sistema.
     */
    public function getAttribute(string $key, mixed $default = null): mixed
    {
        return $this->attributes[$key] ?? $default;
    }

    /**
     * getAttributes
     *
     * Retorna todos os atributos internos adicionados à requisição.
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }
}
