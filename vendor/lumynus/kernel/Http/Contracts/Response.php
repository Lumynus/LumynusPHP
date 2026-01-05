<?php

namespace Lumynus\Http\Contracts;

interface Response
{
    public function status(int $code): self;

    public function getStatus(): int;

    public function header(string $name, string $value): self;

    public function getHeaders(): array;

    public function json(mixed $data = null): void;

    public function html(?string $html = null): void;

    public function text(?string $text = null): void;

    public function file(string $filePath, bool $download = false): void;

    public function redirect(string $url): void;

    public function return(string $content = ''): void;

    public function send(string $content = ''): void;
}
