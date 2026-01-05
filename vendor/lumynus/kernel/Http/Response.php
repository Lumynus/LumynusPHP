<?php

declare(strict_types=1);

namespace Lumynus\Http;

use Lumynus\Bundle\Framework\LumaClasses;
use Lumynus\Http\Contracts\Response as ResponseInterface;

final class Response extends LumaClasses implements  ResponseInterface
{

    /**
     * Código de status HTTP da resposta.
     *
     * @var int
     */
    private int $statusCode = 200;

    /**
     * Cabeçalhos HTTP da resposta.
     *
     * @var array
     */
    private array $headers = [];

    /**
     * Corpo da resposta.
     *
     * @var mixed
     */
    private mixed $body;

    /**
     * Respostas HTTP padrão.
     *
     * @var array
     */
    private array $responses = [
        // 1xx - Informational
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',
        103 => 'Early Hints',

        // 2xx - Success
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

        // 3xx - Redirection
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        307 => 'Temporary Redirect',
        308 => 'Permanent Redirect',

        // 4xx - Client Errors
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

        // 5xx - Server Errors
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
     * @param int $code Código de status HTTP.
     * @return self
     */
    public function status(int $code): self
    {
        $this->statusCode = $code;
        return $this;
    }

    /**
     * Recupera o código registrado
     */
    public function getStatus() : int {
        return $this->statusCode;
    }

    /**
     * Define um cabeçalho HTTP para a resposta.
     *
     * @param string $name Nome do cabeçalho.
     * @param string $value Valor do cabeçalho.
     * @return self
     */
    public function header(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    /**
     * Recupera os cabeçalhos criados
     */
    public function getHeaders() : array {
        return $this->headers;
    }

    /**
     * Define o corpo da resposta.
     *
     * @param mixed $body Corpo da resposta.
     * @return self
     */
    public function json(mixed $data = null): void
    {
        $this->header('Content-Type', 'application/json');

        $json = json_encode($data, JSON_UNESCAPED_UNICODE);

        if (json_last_error() !== JSON_ERROR_NONE) {
            http_response_code(500);
            echo json_encode([
                'error' => 'Failed to encode JSON: ' . json_last_error_msg()
            ]);
            exit;
        }

        http_response_code($this->statusCode);

        if ($this->statusCode !== 200 && ($data === null || $data === [] || $data === '')) {
            echo json_encode([$this->responses[$this->statusCode] ?? '500 Internal Server Error']);
            exit;
        }

        foreach ($this->headers as $key => $value) {
            header("$key: $value");
        }

        echo $json;
        exit;
    }

    /**
     * Define o corpo da resposta como HTML.
     *
     * @param null|string $html Corpo da resposta em HTML.
     * @return void
     */
    public function html(?string $html = null): void
    {
        $this->header('Content-Type', 'text/html; charset=UTF-8');

        http_response_code($this->statusCode);


        if ($this->statusCode !== 200 && ($html === null || $html === '')) {
            echo $this->responses[$this->statusCode] ?? '500 Internal Server Error';
            exit;
        }

        foreach ($this->headers as $key => $value) {
            header("$key: $value");
        }

        echo $html;
        exit;
    }

    /**
     * Define o corpo da resposta como texto simples.
     *
     * @param null|string $text Corpo da resposta em texto simples.
     * @return void
     */
    public function text(?string $text = null): void
    {
        $this->header('Content-Type', 'text/plain; charset=UTF-8');

        http_response_code($this->statusCode);

        if ($this->statusCode !== 200 && ($text === null || $text === '')) {
            echo $this->responses[$this->statusCode] ?? '500 Internal Server Error';
            exit;
        }

        foreach ($this->headers as $key => $value) {
            header("$key: $value");
        }

        echo $text;
        exit;
    }

    /**
     * Define o corpo da resposta como um arquivo para download.
     *
     * @param string $filePath Caminho do arquivo a ser enviado.
     * @return void
     */
    public function file(string $filePath, bool $download = false): void
    {
        if (!file_exists($filePath)) {
            http_response_code(404);
            echo 'File not found';
            exit;
        }

        $mime = mime_content_type($filePath) ?: 'application/octet-stream';
        $this->header('Content-Type', $mime);

        $disposition = $download ? 'attachment' : 'inline';
        $this->header('Content-Disposition', "$disposition; filename=\"" . basename($filePath) . "\"");
        $this->header('Content-Length', (string) filesize($filePath));

        http_response_code($this->statusCode);

        foreach ($this->headers as $key => $value) {
            header("$key: $value");
        }

        // Leitura eficiente
        $fp = fopen($filePath, 'rb');
        if ($fp === false) {
            http_response_code(500);
            echo 'Failed to open file.';
            exit;
        }

        while (!feof($fp)) {
            echo fread($fp, 8192);
            flush();
        }

        fclose($fp);
        exit;
    }

    /**
     * Redireciona para uma URL especificada.
     *
     * @param string $url URL para redirecionamento.
     * @return void
     */
    public function redirect(string $url): void
    {
        http_response_code(302);
        header('Location: ' . $url);
        exit;
    }


    /**
     * Retorna o código de status HTTP atual.
     *
     * @return int Código de status HTTP.
     */
    public function return(string $text = ''): void
    {
        http_response_code($this->statusCode);
        foreach ($this->headers as $key => $value) {
            header("$key: $value");
        }
        echo !empty($text) ? $text : $this->responses[$this->statusCode] ?? '500 Internal Server Error';
        exit;
    }

     /**
     * Retorna o código de status HTTP atual.
     *
     * @return int Código de status HTTP.
     */
    public function send(string $text = ''): void
    {
        http_response_code($this->statusCode);
        foreach ($this->headers as $key => $value) {
            header("$key: $value");
        }
        echo !empty($text) ? $text : $this->responses[$this->statusCode] ?? '500 Internal Server Error';
        exit;
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
