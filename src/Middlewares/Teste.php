<?php

namespace App\Middlewares;

use Lumynus\Bundle\Framework\LumynusMiddleware;

class Teste extends LumynusMiddleware
{

    public function handle($requeste)
    {

        return false;
     
    }
}
