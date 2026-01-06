<?php

namespace Lumynus\Http\Contracts;

interface Response
{
    /**
     * Define o código de status HTTP.
     */
    public function status(int $code): self;

    /**
     * Retorna o código de status HTTP atual.
     */
    public function getStatus(): int;

    /**
     * Define um cabeçalho HTTP.
     */
    public function header(string $name, string $value): self;

    /**
     * Retorna todos os cabeçalhos definidos.
     */
    public function getHeaders(): array;

    /**
     * Envia uma resposta JSON.
     */
    public function json(mixed $data = null): self;

    /**
     * Envia uma resposta HTML.
     */
    public function html(?string $html = null): self;

    /**
     * Envia uma resposta em texto puro.
     */
    public function text(?string $text = null): self;

    /**
     * Envia um arquivo ao cliente.
     *
     * @param bool $download Força download se true.
     */
    public function file(string $filePath, bool $download = false): self;

    /**
     * Envia um redirecionamento HTTP.
     */
    public function redirect(string $url): self;

    /**
     * Envia a resposta ao cliente.
     */
    public function send(string $content = ''): self;
}
