<?php

namespace App\Middlewares;

use Lumynus\Bundle\Framework\LumynusMiddleware;
use Lumynus\Http\Contracts\Request;
use Lumynus\Http\Contracts\Response;

class Teste extends LumynusMiddleware
{

    public function handle(Request $req, Response $res)
    {

        if (!$req->getHeaders()['token']) {
            return false; // Interrompre o fluxo; Controller não é utilizado
        }

        $req->setAttribute('testado', 'ok'); // Cria um atributo que pode ser recuperado pelo Controller
    }
}
