<?php

namespace Lumynus\Http\Contracts;

interface Response
{
    /**
     * Define o código de status HTTP da resposta.
     *
     * @param int $code Código HTTP (100–599).
     * @return self
     */
    public function status(int $code): self;

    /**
     * Obtém o código de status HTTP atual.
     *
     * @return int
     */
    public function getStatus(): int;

    /**
     * Define um cabeçalho HTTP.
     * Protege contra CRLF Injection.
     *
     * @param string $name Nome do cabeçalho.
     * @param string $value Valor do cabeçalho.
     * @return self
     */
    public function header(string $name, string $value): self;

    /**
     * Retorna todos os cabeçalhos definidos.
     *
     * @return array<string,string>
     */
    public function getHeaders(): array;

    /**
     * Retorna o corpo da resposta.
     *
     * @return string|null
     */
    public function getBody(): ?string;

    /**
     * Retorna o stream do arquivo.
     *
     * @return mixed
     */
    public function getFileStream(): mixed;

    /**
     * Retorna se a resposta já foi enviada.
     *
     * @return bool
     */
    public function isSent(): bool;

    /**
     * Retorna o tamanho do corpo da resposta.
     *
     * @return int
     */
    public function getBodyLength(): int;

    /**
     * Retorna o tamanho do stream do arquivo.
     *
     * @return int
     */
    public function getFileLength(): int;

    /**
     * Prepara uma resposta no formato JSON.
     *
     * @param mixed $data Dados a serem serializados.
     * @return self
     */
    public function json(mixed $data = null): self;

    /**
     * Prepara uma resposta HTML.
     *
     * @param string|null $html Conteúdo HTML.
     * @return self
     */
    public function html(?string $html = null): self;

    /**
     * Prepara uma resposta em texto simples.
     *
     * @param string|null $text Texto da resposta.
     * @return self
     */
    public function text(?string $text = null): self;

    /**
     * Prepara o envio de um arquivo (Streaming).
     *
     * Utiliza stream resource para evitar alto consumo de memória.
     *
     * @param string $filePath Caminho absoluto do arquivo.
     * @param bool $download Se true, força o download.
     * @return self
     */
    public function file(string $filePath, bool $download = false): self;

    /**
     * Prepara um redirecionamento HTTP.
     *
     * @param string $url URL de destino.
     * @param int $code Código (301, 302, etc).
     * @return self
     */
    public function redirect(string $url): self;

    /**
     * Prepara uma resposta genérica.
     *
     * @param string $text Conteúdo opcional.
     * @return self
     */
    public function send(string $content = ''): self;

    /**
     * Envia efetivamente a resposta ao cliente (Headers + Body).
     *
     * DEVE ser chamado pelo Route ou Kernel ao final da execução.
     *
     * @return void
     */
    public function dispatch(): void;
}
