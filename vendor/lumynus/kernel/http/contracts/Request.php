<?php
declare(strict_types=1);

namespace Lumynus\Http\Contracts;

interface Request
{
    public function getMethod(): string;
    public function getUri(): string;

    public function getQueryParams(): array;
    public function getParsedBody(): array|null;
    public function getHeaders(): array;

    public function get(string $key, mixed $default = null): mixed;
    public function post(string $key, mixed $default = null): mixed;
    
    public function file(string $key, mixed $default = null): mixed;
    public function files(): mixed;

    public function server(): mixed;

    public function setAttribute(string $key, mixed $value): void;
    public function unsetAttribute(string $key): void;
    public function getAttribute(string $key, mixed $default = null): mixed;
    public function getAttributes(): array;


}
