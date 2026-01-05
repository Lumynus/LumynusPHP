<?php
declare(strict_types=1);

namespace Lumynus\Http;

use Lumynus\Http\Contracts\Request as RequestInterface;

final class Request implements RequestInterface
{
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

    public function getMethod(): string { return $this->method; }
    public function getUri(): string { return $this->uri; }

    public function getQueryParams(): array { return $this->query; }
    public function getParsedBody(): array|null { return $this->body; }
    public function getHeaders(): array { return $this->headers; }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }

    public function post(string $key, mixed $default = null): mixed
    {
        return $this->post[$key] ?? $default;
    }
}
