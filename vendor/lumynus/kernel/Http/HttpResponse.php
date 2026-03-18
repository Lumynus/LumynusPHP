<?php

declare(strict_types=1);

namespace Lumynus\Http;

use Lumynus\Framework\LumaClasses;
use Lumynus\Http\Contracts\Response as ResponseInterface;
use Lumynus\Framework\Config;
use Lumynus\Framework\Logs;

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
     * @var array<string, array{value: string, replace: bool}>
     */
    private array $headers = [
        // 'Content-Type' => ['value' => '...', 'replace' => true]
    ];

    /**
     * Conteúdo do corpo da resposta (String ou NULL).
     */
    private ?string $body = null;

    /**
     * Caminho do arquivo para streaming (download/file).
     * @var string|null
     */
    private ?string $filePath = null;

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
    public function header(string $name, string $value, bool $replace = true): self
    {
        $name = trim($name);

        if ($name === '' || preg_match('/[^A-Za-z0-9\-]/', $name)) {
            throw new \InvalidArgumentException('Invalid header name');
        }

        $cleanValue = str_replace(["\r", "\n"], '', $value);

        if (!$replace && isset($this->headers[$name])) {
            $this->headers[$name]['value'] .= ', ' . $cleanValue;
            $this->headers[$name]['replace'] = false;
        } else {
            $this->headers[$name] = [
                'value' => $cleanValue,
                'replace' => $replace
            ];
        }

        return $this;
    }

    /**
     * Retorna todos os cabeçalhos definidos.
     *
     * @return array<string,array{value:string,replace:bool}>
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Retorna o corpo da resposta.
     *
     * @return string|null
     */
    public function getBody(): ?string
    {
        return $this->body;
    }

    /**
     * Retorna o stream do arquivo.
     *
     * @return mixed
     */
    public function getFileStream(): mixed
    {
        return $this->fileStream;
    }

    /**
     * Retorna se a resposta já foi enviada.
     *
     * @return bool
     */
    public function isSent(): bool
    {
        return $this->sent;
    }

    /**
     * Retorna o tamanho do corpo da resposta.
     *
     * @return int
     */
    public function getBodyLength(): int
    {
        return $this->body !== null ? strlen($this->body) : 0;
    }

    /**
     * Retorna o tamanho do stream do arquivo.
     *
     * @return int
     */
    public function getFileLength(): int
    {
        if (is_resource($this->fileStream)) {
            $stats = fstat($this->fileStream);
            return (int) ($stats['size'] ?? 0);
        }

        if ($this->filePath !== null && is_file($this->filePath)) {
            $size = filesize($this->filePath);
            return $size !== false ? $size : 0;
        }

        return 0;
    }

    /**
     * Prepara uma resposta no formato JSON.
     *
     * @param mixed $data Dados a serem serializados.
     * @return self
     */
    public function json(mixed $data = null): self
    {
        $this->resetStream();
        $this->header('Content-Type', 'application/json; charset=UTF-8');

        if ($this->statusCode !== 200 && $data === null) {
            $data = ['message' => $this->responses[$this->statusCode] ?? 'Unknown Status'];
        }

        try {
            $this->body = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            $this->status(500);
            $this->body = '{"error":"Failed to encode JSON response."}';
        }

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
        $this->resetStream();
        $this->header('Content-Type', 'text/html; charset=UTF-8');

        $this->body = $html ?? ($this->statusCode !== 200 ? ($this->responses[$this->statusCode] ?? '') : '');

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
        $this->resetStream();
        $this->header('Content-Type', 'text/plain; charset=UTF-8');

        $this->body = $text ?? ($this->statusCode !== 200 ? ($this->responses[$this->statusCode] ?? '') : '');

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
        $realPath = realpath($filePath);

        if ($realPath === false) {
            return $this->status(404)->text('File not found.');
        }

        $base = realpath(Config::getApplicationConfig()['path']['files']);

        if ($base === false) {
            return $this->status(500)->text('Invalid files base path.');
        }

        $base = rtrim($base, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        if (!str_starts_with($realPath, $base)) {
            return $this->status(403)->text('Access denied.');
        }

        if (!is_file($realPath) || !is_readable($realPath)) {
            return $this->status(404)->text('File not readable.');
        }

        if (str_starts_with(basename($realPath), '.')) {
            return $this->status(403)->text('Access denied.');
        }

        $this->resetStream();
        $this->body = null;

        try {
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($realPath) ?: null;
        } catch (\Throwable) {
            $mime = null;
        }

        $mime = $mime ?: 'application/octet-stream';
        $this->header('Content-Type', $mime);

        $disposition = $download ? 'attachment' : 'inline';
        $filename = str_replace('"', '', basename($realPath) ?: 'file');
        $this->header('Content-Disposition', sprintf('%s; filename="%s"', $disposition, $filename));

        $this->header('Cache-Control', 'no-cache, no-store, must-revalidate');
        $this->header('Pragma', 'no-cache');
        $this->header('Expires', '0');

        $stream = fopen($realPath, 'rb');

        if ($stream === false) {
            return $this->status(500)->text('Failed to open file stream.');
        }

        $stats = fstat($stream);
        if ($stats !== false && isset($stats['size'])) {
            $this->header('Content-Length', (string) $stats['size']);
        }

        $this->fileStream = $stream;
        $this->filePath = $realPath;

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
        $this->resetStream();
        $this->status($code);
        $this->header('Location', str_replace(["\r", "\n"], '', $url));
        $this->body = null;

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
        $this->resetStream();
        if (!isset($this->headers['Content-Type'])) {
            $this->header('Content-Type', 'text/plain; charset=UTF-8');
        }
        $this->body = $text !== '' ? $text : ($this->responses[$this->statusCode] ?? '');

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
        if ($this->sent === true) {
            return;
        }

        $this->sent = true;

        $canSendHeaders = !headers_sent();

        if (!$canSendHeaders && $this->fileStream !== null) {
            throw new \RuntimeException('Cannot stream file, headers already sent.');
        }

        if ($canSendHeaders) {
            $protocol = $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.1';
            if (!str_starts_with($protocol, 'HTTP/')) {
                $protocol = 'HTTP/1.1';
            }

            header(sprintf(
                '%s %d %s',
                $protocol,
                $this->statusCode,
                $this->responses[$this->statusCode] ?? ''
            ));

            if (
                $this->body !== null &&
                $this->fileStream === null &&
                !isset($this->headers['Content-Length'])
            ) {
                $this->headers['Content-Length'] = [
                    'value' => (string) strlen($this->body),
                    'replace' => true
                ];
            }

            foreach ($this->headers as $key => $data) {
                if (!isset($data['value'], $data['replace'])) {
                    continue;
                }

                header("$key: {$data['value']}", (bool) $data['replace']);
            }
        }

        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        if ($method === 'HEAD') {
            if (is_resource($this->fileStream)) {
                fclose($this->fileStream);
                $this->fileStream = null;
            }
            return;
        }


        if (is_resource($this->fileStream)) {
            while (ob_get_level() > 0) {
                ob_end_clean();
            }

            while (!feof($this->fileStream)) {
                $chunk = fread($this->fileStream, 8192);

                if ($chunk === false) {
                    Logs::register('Error', 'Failed to read file stream.');
                    break;
                }

                if ($chunk === '') {
                    continue;
                }

                echo $chunk;

                if (ob_get_level() > 0) {
                    ob_flush();
                }

                flush();
            }

            fclose($this->fileStream);
            $this->fileStream = null;

            return;
        }

        if ($this->body !== null) {
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
        $this->filePath = null;

        foreach (['Content-Disposition', 'Content-Length'] as $h) {
            unset($this->headers[$h]);
        }
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
            'BodyLength' => $this->body !== null
                ? strlen($this->body)
                : ($this->fileStream ? 'Stream Resource' : 0)
        ];
    }
}
