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

        // $this->response()->status(500)->return();
    }
}
