<?php

declare(strict_types=1);

namespace Lumynus\Http;

use Lumynus\Framework\LumaClasses;
use Lumynus\Http\Contracts\Response as ResponseInterface;

/**
 * Gerencia a resposta HTTP enviada ao cliente.
 * Implementa o padrão de "Execução Adiada" (Deferred Execution),
 * onde o conteúdo é preparado e enviado apenas no momento do dispatch.
 */
final class HttpResponse extends LumaClasses implements ResponseInterface
{
    /**
     * Código de status HTTP.
     */
    private int $statusCode = 200;

    /**
     * Lista de cabeçalhos HTTP.
     * @var array<string,string>
     */
    private array $headers = [];

    /**
     * Conteúdo do corpo da resposta (String ou NULL).
     */
    private ?string $body = null;

    /**
     * Recurso de arquivo para streaming (download/file).
     * @var resource|null
     */
    private $fileStream = null;

    /**
     * Indica se a resposta já foi enviada ao navegador.
     */
    private bool $sent = false;

    /**
     * Mensagens padrão para códigos HTTP.
     */
    private array $responses = [
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',
        103 => 'Early Hints',
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-Status',
        208 => 'Already Reported',
        226 => 'IM Used',
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        307 => 'Temporary Redirect',
        308 => 'Permanent Redirect',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Payload Too Large',
        414 => 'URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Range Not Satisfiable',
        417 => 'Expectation Failed',
        418 => 'I\'m a teapot',
        421 => 'Misdirected Request',
        422 => 'Unprocessable Entity',
        423 => 'Locked',
        424 => 'Failed Dependency',
        425 => 'Too Early',
        426 => 'Upgrade Required',
        428 => 'Precondition Required',
        429 => 'Too Many Requests',
        431 => 'Request Header Fields Too Large',
        451 => 'Unavailable For Legal Reasons',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        506 => 'Variant Also Negotiates',
        507 => 'Insufficient Storage',
        508 => 'Loop Detected',
        510 => 'Not Extended',
        511 => 'Network Authentication Required'
    ];

    /**
     * Define o código de status HTTP da resposta.
     *
     * @param int $code Código HTTP (100–599).
     * @return self
     */
    public function status(int $code): self
    {
        if ($code < 100 || $code > 599) {
            throw new \InvalidArgumentException("Invalid HTTP status code");
        }
        $this->statusCode = $code;
        return $this;
    }

    /**
     * Obtém o código de status HTTP atual.
     *
     * @return int
     */
    public function getStatus(): int
    {
        return $this->statusCode;
    }

    /**
     * Define um cabeçalho HTTP.
     * Protege contra CRLF Injection.
     *
     * @param string $name Nome do cabeçalho.
     * @param string $value Valor do cabeçalho.
     * @return self
     */
    public function header(string $name, string $value): self
    {
        $cleanValue = str_replace(["\r", "\n"], '', $value);
        $this->headers[$name] = $cleanValue;
        return $this;
    }

    /**
     * Retorna todos os cabeçalhos definidos.
     *
     * @return array<string,string>
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Prepara uma resposta no formato JSON.
     *
     * @param mixed $data Dados a serem serializados.
     * @return self
     */
    public function json(mixed $data = null): self
    {
        $this->header('Content-Type', 'application/json');

        if ($this->statusCode !== 200 && empty($data)) {
            $data = ['message' => $this->responses[$this->statusCode] ?? 'Unknown Status'];
        }

        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->status(500);
            $this->body = json_encode(['error' => 'JSON Encode Error: ' . json_last_error_msg()]);
        } else {
            $this->body = $json;
        }

        $this->resetStream();
        return $this;
    }

    /**
     * Prepara uma resposta HTML.
     *
     * @param string|null $html Conteúdo HTML.
     * @return self
     */
    public function html(?string $html = null): self
    {
        $this->header('Content-Type', 'text/html; charset=UTF-8');

        $this->body = $html ?? ($this->statusCode !== 200 ? ($this->responses[$this->statusCode] ?? '') : '');

        $this->resetStream();
        return $this;
    }

    /**
     * Prepara uma resposta em texto simples.
     *
     * @param string|null $text Texto da resposta.
     * @return self
     */
    public function text(?string $text = null): self
    {
        $this->header('Content-Type', 'text/plain; charset=UTF-8');

        $this->body = $text ?? ($this->statusCode !== 200 ? ($this->responses[$this->statusCode] ?? '') : '');

        $this->resetStream();
        return $this;
    }

    /**
     * Prepara o envio de um arquivo (Streaming).
     *
     * Utiliza stream resource para evitar alto consumo de memória.
     *
     * @param string $filePath Caminho absoluto do arquivo.
     * @param bool $download Se true, força o download.
     * @return self
     */
    public function file(string $filePath, bool $download = false): self
    {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            return $this->status(404)->text('File not found or not readable.');
        }

        $this->resetStream();
        $this->body = null;

        $mime = mime_content_type($filePath) ?: 'application/octet-stream';
        $this->header('Content-Type', $mime);

        $disposition = $download ? 'attachment' : 'inline';
        $filename = basename($filePath);
        $this->header('Content-Disposition', "$disposition; filename=\"$filename\"");
        $this->header('Content-Length', (string) filesize($filePath));


        $this->fileStream = fopen($filePath, 'rb');

        return $this;
    }

    /**
     * Prepara um redirecionamento HTTP.
     *
     * @param string $url URL de destino.
     * @param int $code Código (301, 302, etc).
     * @return self
     */
    public function redirect(string $url, int $code = 302): self
    {
        $this->status($code);
        $this->header('Location', $url);
        $this->body = null;
        $this->resetStream();
        return $this;
    }

    /**
     * Prepara uma resposta genérica.
     *
     * @param string $text Conteúdo opcional.
     * @return self
     */
    public function send(string $text = ''): self
    {
        $content = !empty($text) ? $text : ($this->responses[$this->statusCode] ?? '');
        $this->body = $content;
        $this->resetStream();
        return $this;
    }

    /**
     * Envia efetivamente a resposta ao cliente (Headers + Body).
     *
     * DEVE ser chamado pelo Route ou Kernel ao final da execução.
     *
     * @return void
     */
    public function dispatch(): void
    {
        if ($this->sent || headers_sent()) {
            return;
        }

        $this->sent = true;

        http_response_code($this->statusCode);

        foreach ($this->headers as $key => $value) {
            header("$key: $value");
        }

        if (is_resource($this->fileStream)) {
            if (ob_get_level()) ob_end_clean();

            while (!feof($this->fileStream)) {
                echo fread($this->fileStream, 8192);
                flush();
            }
            fclose($this->fileStream);
        } elseif ($this->body !== null) {
            echo $this->body;
        }
    }

    /**
     * Helper para fechar streams abertos ao mudar o tipo de resposta.
     */
    private function resetStream(): void
    {
        if (is_resource($this->fileStream)) {
            fclose($this->fileStream);
        }
        $this->fileStream = null;
    }

    /**
     * Destrutor para garantir limpeza de recursos.
     */
    public function __destruct()
    {
        $this->resetStream();
    }

    public function __debugInfo(): array
    {
        return [
            'Lumynus' => "Framework PHP",
            'Status' => $this->statusCode,
            'Headers' => $this->headers,
            'BodyLength' => $this->body ? strlen($this->body) : ($this->fileStream ? 'Stream Resource' : 0)
        ];
    }
}
