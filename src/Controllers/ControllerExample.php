<?php

namespace App\Controllers;


use Lumynus\Bundle\Framework\LumynusController;


class ControllerExample extends LumynusController
{


    public function index($req,mixed $a = 'oi')
    {

        return  $this->response()
        ->header('Content-Type', 'application/json')
         ->status(200)
         ->json([
            'mensage' => 'Olá, Mundo!',
            'data_from_middleware' => $a
        ]);
       

    }
}
