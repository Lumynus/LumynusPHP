<?php

declare(strict_types=1);

namespace Lumynus\Http;

class HttpException extends \Exception
{
    protected string $format;
    protected int $statusCode;

    public function __construct(
        string $message,
        int $statusCode = 500,
        string $format = 'html'
    ) {
        parent::__construct($message);
        $this->statusCode = $statusCode;
        $this->format = $format;
    }

    public function getFormat(): string
    {
        return $this->format;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}
