<?php

declare(strict_types=1);

namespace App\Commands;
use Lumynus\Bundle\Framework\Commands;

class OiCommand extends Commands
{
    public function oi($commands)
    {
        
        $this->respond()->info("Oi! Comandos recebidos: " . implode(", ", $commands));
    }
}