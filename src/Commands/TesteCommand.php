<?php

declare(strict_types=1);

namespace App\Commands;
use Lumynus\Bundle\Framework\LumynusCommands;

class TesteCommand extends LumynusCommands
{
    public function handle($commands)
    {
        $this->respond()->info("Oi! Comandos recebidos: " . implode(", ", $commands));
    }
}
