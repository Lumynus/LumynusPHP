<?php

namespace App\Controllers;


use Lumynus\Bundle\Framework\LumynusController;


class ControllerExample extends LumynusController
{

    public function index($request, $posts)
    {

        $this->response()->html(
            $this->renderView('index.html', [
                'conteudo' => 'Olá, mundo!',
            ])
        );

        $o = $this->sanitizer()->int('123a');

        var_dump($request);


        $this->response()->status(500)->return();
    }



    public function novo()
    {

        $a = $this->httpClient()->request('https://jsonplaceholder.typicode.com/posts/1', 'GET');

        var_dump($a);
    }


    public function teste()
    {

        $a = $this->lumaHTTP();
        $a->sslVerification(false)
            ->rateLimit(1, 10, 'wait')
            ->get('https://viacep.com.br/ws/01001000/json/');

        $b = $a->getResponse();

        // var_dump($a->debugTest());

        var_dump($b);
    }


    public function teste2()
    {

        var_dump($_ENV, getenv());
    }


    public function teste3()
    {

        $teste = \Lumynus\Bundle\Framework\Config::getAplicationConfig();

        var_dump($teste['App']);
    }
}
