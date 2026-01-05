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
    public function json(mixed $data = null): void;

    /**
     * Envia uma resposta HTML.
     */
    public function html(?string $html = null): void;

    /**
     * Envia uma resposta em texto puro.
     */
    public function text(?string $text = null): void;

    /**
     * Envia um arquivo ao cliente.
     *
     * @param bool $download Força download se true.
     */
    public function file(string $filePath, bool $download = false): void;

    /**
     * Envia um redirecionamento HTTP.
     */
    public function redirect(string $url): void;

    /**
     * Define o conteúdo da resposta sem enviar imediatamente.
     */
    public function return(string $content = ''): void;

    /**
     * Envia a resposta ao cliente.
     */
    public function send(string $content = ''): void;
}
