<?php

namespace App\Controllers;


use Lumynus\Bundle\Framework\LumynusController;


class ControllerExample extends LumynusController
{

    public function index($request, $posts)
    {

        // $this->response()->html(
        //     $this->renderView('index.html', [
        //         'conteudo' => 'Olá, mundo!',
        //     ])
        // );

        $o = $this->sanitizer()->int('123a');

        var_dump($o);


        // $this->response()->status(500)->return();
    }
}
