<?php

declare(strict_types=1);

namespace Lumynus\Http\Contracts;

interface Request
{
    /**
     * Retorna o método HTTP da requisição.
     */
    public function getMethod(): string;

    /**
     * Retorna a URI da requisição.
     */
    public function getUri(): string;

    /**
     * Retorna os parâmetros da query string.
     *
     * @return array<string, mixed>
     */
    public function getQueryParams(): array;

    /**
     * Retorna o corpo da requisição já parseado.
     *
     * @return array<string, mixed>|null
     */
    public function getParsedBody(): array|null;

    /**
     * Retorna dados do corpo da requisição.
     *
     * @param string $key     Chave a ser recuperada.
     * @param mixed  $default Valor padrão caso a chave não exista.
     */
    public function body(string|null $key, mixed $default = null): mixed;

    /**
     * Retorna todos os headers da requisição.
     *
     * @return array<string, mixed>
     */
    public function getHeaders(): array;

    /**
     * Retorna um valor da query string.
     *
     * @param string $key
     * @param mixed  $default
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Retorna um arquivo enviado na requisição.
     *
     * @param string $key
     * @param mixed  $default
     */
    public function file(string $key, mixed $default = null): mixed;

    /**
     * Retorna todos os arquivos enviados.
     */
    public function files(): array;

    /**
     * Retorna informações do servidor.
     */
    public function server(): mixed;

    /**
     * Define um atributo na requisição.
     *
     * @param string $key
     * @param mixed  $value
     */
    public function setAttribute(string $key, mixed $value): void;

    /**
     * Remove um atributo da requisição.
     */
    public function unsetAttribute(string $key): void;

    /**
     * Retorna um atributo da requisição.
     *
     * @param string $key
     * @param mixed  $default
     */
    public function getAttribute(string $key, mixed $default = null): mixed;

    /**
     * Retorna todos os atributos da requisição.
     *
     * @return array<string, mixed>
     */
    public function getAttributes(): array;
}
