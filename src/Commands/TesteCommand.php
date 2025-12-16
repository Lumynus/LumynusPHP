<?php

declare(strict_types=1);

namespace App\Commands;
use Lumynus\Bundle\Framework\Commands;

class TesteCommand extends Commands
{
    public function handle($commands)
    {
        $this->respond()->info("Oi! Comandos recebidos: " . implode(", ", $commands));
    }
}