<?php

declare(strict_types=1);

namespace Lumynus\Http;

use Lumynus\Http\Contracts\Request as RequestInterface;

final class HttpRequest implements RequestInterface
{

    private array $attributes = [];
    private bool $bodyParsed = false;

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
    ) {
        $this->headers = $this->normalizeHeaders($headers);
    }

    /**
     * fromGlobals
     *
     * Cria uma instância de Request a partir das variáveis globais do PHP,
     * centralizando o acesso aos dados da requisição HTTP atual.
     */
    public static function fromGlobals(): self
    {
        return new self(
            strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET'),
            $_SERVER['REQUEST_URI'] ?? '/',
            $_GET,
            $_POST,
            self::headersFromGlobals(),
            $_FILES,
            $_SERVER,
            null
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
            if (
                str_starts_with($key, 'HTTP_') ||
                in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH'], true)
            ) {
                $name = strtolower(
                    str_replace('_', '-', str_replace('HTTP_', '', $key))
                );

                $headers[$name] = $value;
            }
        }

        return $headers;
    }

    /**
     * normalizeHeaders
     *
     * Normaliza os nomes dos cabeçalhos HTTP para minúsculas e com hífens.
     */
    private function normalizeHeaders(array $headers): array
    {
        $normalized = [];

        foreach ($headers as $name => $value) {
            $key = strtolower(str_replace('_', '-', $name));
            $normalized[$key] = $value;
        }

        return $normalized;
    }


    /**
     * Analisando Body
     */
    private function parseBody(): void
    {
        if ($this->bodyParsed) {
            return;
        }

        $this->bodyParsed = true;

        $contentType = $this->headers['content-type'] ?? '';
        $method = $this->method;

        if ($method === 'GET') {
            $this->body = null;
            return;
        }

        if (
            !empty($this->post) &&
            str_contains($contentType, 'application/x-www-form-urlencoded')
        ) {
            $this->body = $this->post;
            return;
        }

        $raw = file_get_contents('php://input');

        if ($raw === '' || $raw === false) {
            $this->body = null;
            return;
        }

        if (str_contains($contentType, 'application/json')) {
            $decoded = json_decode($raw, true);
            $this->body = is_array($decoded) ? $decoded : null;
            return;
        }

        if (str_contains($contentType, 'application/x-www-form-urlencoded')) {
            parse_str($raw, $parsed);
            $this->body = $parsed ?: null;
            return;
        }

        if (str_contains($contentType, 'multipart/form-data')) {
            $this->body = $this->post ?: null;
            return;
        }

        $this->body = null;
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
        $this->parseBody();
        return $this->body;
    }

    /**
     * Retorna dados do corpo da requisição HTTP.
     *
     * Suporta JSON, x-www-form-urlencoded e multipart/form-data,
     * independentemente do método HTTP (POST, PUT, PATCH).
     *
     * @param string|null $key     Chave a ser recuperada ou null para todos os dados.
     * @param mixed       $default Valor padrão caso a chave não exista.
     *
     * @return mixed Array com todos os dados, valor específico ou null.
     */
    public function body(string|null $key = null, mixed $default = null): mixed
    {
        $data = $this->getParsedBody();

        if ($key === null) {
            return $data;
        }

        return $data[$key] ?? $default;
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
    public function files(): array
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