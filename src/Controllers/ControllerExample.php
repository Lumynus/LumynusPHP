<?php

declare(strict_types=1);

namespace App\Controllers;

use Lumynus\Framework\AbstractController;
use Lumynus\Http\Contracts\Request;
use Lumynus\Http\Contracts\Response;

class ControllerExample extends AbstractController
{
    public function index(Response $res, Request $req): Response
    {

        /**
         * Request - Métodos disponíveis
         */
        $req->get('slug', null); //Se slug não existir fica null
        $req->getHeaders();
        $req->getMethod();
        $req->getParsedBody();
        $req->getQueryParams();
        $req->getUri();


        /**
         * Response - Métodos disponíveis
         */

        //json
        $res->status(200)
            ->json(['Sucesso' => true]);

        //html
        $res->status(200)
            ->html('<p>Sucesso: true</p>');

        //text
        $res->status(200)
            ->text('Sucesso: true');

        //Redirecionamento
        $res
            ->redirect('https://site.com');

        // Arquivos
        return $res
            ->file('arquivo.pdf', download: true); // forçar o download do arquivo
    }
}
