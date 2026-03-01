<?php

declare(strict_types=1);

namespace Lumynus\Console\Contracts;

interface Terminal
{
    public function getAll(): array;
    public function command(): string;
    public function method(): ?string;
    public function params(): array;
}
