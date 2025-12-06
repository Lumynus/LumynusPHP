<?php

namespace App\Controllers;


use Lumynus\Bundle\Framework\LumynusController;


class ControllerExample extends LumynusController
{


    public function index($req)
    {

       
        $this->response()->html($this->renderView(
            'index.html'
        ));

    }

}
