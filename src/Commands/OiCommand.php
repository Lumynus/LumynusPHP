<?php

declare(strict_types=1);

namespace App\Commands;
use Lumynus\Bundle\Framework\Commands;

class OiCommand extends Commands
{
    public function oi($commands)
    {
        
        echo "Oi, tudo bem? Você está executando o comando: " . implode(' ', $commands) . "\n";
    }
}