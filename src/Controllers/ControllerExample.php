<?php

declare(strict_types=1);

namespace App\Controllers;

use Lumynus\Bundle\Framework\LumynusController;
use Lumynus\Http\Contracts\Request;
use Lumynus\Http\Contracts\Response;

class ControllerExample extends LumynusController
{
    public function index(Response $res, Request $req)
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
         * Request - Obter dados passados pelo Middleware
         */
        $req->getAttribute('chave');
        $req->getAttributes();
        $req->unsetAttribute('chave');

        /**
         * Fluxos
         */
        $this->next('proximaFuncao'); // Continua o fluxo daqui, mas em outro método
        $this->nextTo(\stdClass::class, 'handle'); // Transfere o controle para outro objeto, outro contexto.

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
        $res
            ->file('arquivo.pdf', download: true); // forçar o download do arquivo
    }
}
