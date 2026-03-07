<?php

declare(strict_types=1);

namespace App\Commands;

use Lumynus\Framework\AbstractCommand;

class TesteCommand extends AbstractCommand
{
    public function handle($commands)
    {
        $this->output()->info("Oi! Comandos recebidos: " . implode(", ", $commands));
    }
}
