<?php

declare(strict_types=1);

namespace Lumynus\Http;

use Lumynus\Bundle\Framework\LumaClasses;
use Lumynus\Http\Contracts\Response as ResponseInterface;

final class Response extends LumaClasses implements ResponseInterface
{
    /**
     * Código de status HTTP da resposta.
     *
     * @var int
     */
    private int $statusCode = 200;

    /**
     * Lista de cabeçalhos HTTP da resposta.
     *
     * @var array<string,string>
     */
    private array $headers = [];

    /**
     * Código de status HTTP da resposta.
     *
     * @var int
     */
    private bool $sent = false;

    /**
     * Mensagens padrão para códigos HTTP.
     *
     * @var array<int,string>
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
     * @return self Retorna a própria instância para encadeamento.
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
     * @return int Código de status HTTP.
     */
    public function getStatus(): int
    {
        return $this->statusCode;
    }

    /**
     * Define um cabeçalho HTTP.
     *
     * Protege contra CRLF Injection removendo quebras de linha do valor.
     *
     * @param string $name Nome do cabeçalho.
     * @param string $value Valor do cabeçalho.
     * @return self Retorna a própria instância para encadeamento.
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
     * @return array<string,string> Cabeçalhos HTTP.
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Envia os cabeçalhos HTTP ao cliente.
     *
     * Método interno responsável por definir o status HTTP
     * e emitir os cabeçalhos, respeitando headers já enviados.
     *
     * @return void
     */
    private function sendHeaders(): void
    {
        if (headers_sent()) {
            return;
        }

        http_response_code($this->statusCode);

        foreach ($this->headers as $key => $value) {
            header("$key: $value");
        }
    }

    /**
     * Envia uma resposta no formato JSON.
     *
     * Define o Content-Type apropriado, serializa os dados e
     * emite a resposta apenas uma vez.
     *
     * @param mixed $data Dados a serem serializados em JSON.
     * @return self Retorna a própria instância.
     */
    public function json(mixed $data = null): self
    {
        if ($this->sent) return $this;
        $this->sent = true;

        $this->header('Content-Type', 'application/json');

        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        if (json_last_error() !== JSON_ERROR_NONE) {
            http_response_code(500);
            echo json_encode(['error' => 'JSON Encode Error: ' . json_last_error_msg()]);
            return $this;
        }

        if ($this->statusCode !== 200 && empty($data)) {
            $data = ['message' => $this->responses[$this->statusCode] ?? 'Unknown Status'];
            $json = json_encode($data);
        }

        $this->sendHeaders();
        echo $json;

        return $this;
    }

    /**
     * Envia uma resposta HTML.
     *
     * @param string|null $html Conteúdo HTML da resposta.
     * @return self Retorna a própria instância.
     */
    public function html(?string $html = null): self
    {
        if ($this->sent) return $this;
        $this->sent = true;

        $this->header('Content-Type', 'text/html; charset=UTF-8');

        $this->sendHeaders();

        echo ($html ?? ($this->statusCode !== 200 ? ($this->responses[$this->statusCode] ?? '') : ''));

        return $this;
    }

    /**
     * Envia uma resposta em texto simples.
     *
     * @param string|null $text Texto da resposta.
     * @return self Retorna a própria instância.
     */
    public function text(?string $text = null): self
    {
        if ($this->sent) return $this;
        $this->sent = true;

        $this->header('Content-Type', 'text/plain; charset=UTF-8');

        $this->sendHeaders();

        echo ($text ?? ($this->statusCode !== 200 ? ($this->responses[$this->statusCode] ?? '') : ''));

        return $this;
    }

    /**
     * Envia um arquivo para o cliente.
     *
     * Pode ser exibido inline ou forçar download, conforme o parâmetro.
     *
     * @param string $filePath Caminho do arquivo.
     * @param bool $download Força download se true.
     * @return self Retorna a própria instância.
     */
    public function file(string $filePath, bool $download = false): self
    {
        if ($this->sent) return $this;
        $this->sent = true;

        if (!file_exists($filePath) || !is_readable($filePath)) {
            $this->status(404)->text('File not found or not readable.');
            return $this;
        }

        // Limpa buffer anterior para não corromper o arquivo
        if (ob_get_level()) ob_end_clean();

        $mime = mime_content_type($filePath) ?: 'application/octet-stream';
        $this->header('Content-Type', $mime);

        $disposition = $download ? 'attachment' : 'inline';
        $filename = basename($filePath);
        $this->header('Content-Disposition', "$disposition; filename=\"$filename\"");
        $this->header('Content-Length', (string) filesize($filePath));

        $this->sendHeaders();

        $fp = fopen($filePath, 'rb');
        if ($fp) {
            while (!feof($fp)) {
                echo fread($fp, 8192);
                flush();
            }
            fclose($fp);
        }

        return $this;
    }

    /**
     * Redireciona o cliente para outra URL.
     *
     * @param string $url URL de destino.
     * @param int $code Código HTTP de redirecionamento (padrão 302).
     * @return self Retorna a própria instância.
     */
    public function redirect(string $url, int $code = 302): self
    {
        if ($this->sent) return $this;
        $this->sent = true;

        $this->status($code);
        $this->header('Location', $url);
        $this->sendHeaders();

        return $this;
    }

    /**
     * Envia uma resposta genérica com texto simples.
     *
     * Caso nenhum texto seja informado, utiliza a mensagem padrão
     * do código HTTP atual.
     *
     * @param string $text Texto opcional da resposta.
     * @return self Retorna a própria instância.
     */
    public function send(string $text = ''): self
    {
        if ($this->sent) return $this;
        $this->sent = true;

        $this->sendHeaders();

        echo !empty($text) ? $text : ($this->responses[$this->statusCode] ?? '');

        return $this;
    }

    public function __debugInfo(): array
    {
        return [
            'Lumynus' => "Framework PHP",
            'Status' => $this->statusCode,
            'Headers' => $this->headers
        ];
    }
}
