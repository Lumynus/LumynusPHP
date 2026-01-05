<?php

namespace Lumynus\Console\Contracts;

interface Responder
{
    public function success(string $message): self;
    public function info(string $message, string $colorANSI): self;
    public function error(string $message): self;
}
