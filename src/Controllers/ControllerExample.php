<?php

namespace App\Controllers;


use Lumynus\Bundle\Framework\LumynusController;


class ControllerExample extends LumynusController
{


    public function index($req)
    {

       
        var_dump($req);

    }

}
