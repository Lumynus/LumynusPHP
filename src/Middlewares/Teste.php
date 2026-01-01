<?php

namespace App\Middlewares;

use Lumynus\Bundle\Framework\LumynusMiddleware;
use Lumynus\Http\Contracts\Request;
use Lumynus\Http\Contracts\Response;

class Teste extends LumynusMiddleware
{

    public function handle(Request $req, Response $res)
    {

        //Caso queira interromper, um fluxo use Respose ou return false para o framework utilizar métodos próprios de bloqueio

        if (!$req->get('slug', null)) {
            $res->json(["Error"]);
        }
    }
}
