<?php

namespace App\Middlewares;

use Lumynus\Framework\AbstractMiddleware;
use Lumynus\Http\Contracts\Request;
use Lumynus\Http\Contracts\Response;

class Teste extends AbstractMiddleware
{

    public function handle(Request $req, Response $res): Response
    {

        //Caso queira interromper, um fluxo use Respose ou return false para o framework utilizar métodos próprios de bloqueio

        if (!$req->get('slug', null)) {
           return $res->json(["Error"]);
        }

        return $this->abort();
    }
}
