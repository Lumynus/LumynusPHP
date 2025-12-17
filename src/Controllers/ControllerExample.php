<?php

namespace App\Controllers;

use Lumynus\Bundle\Framework\LumynusController;
use Lumynus\Bundle\Framework\Route;

class ControllerExample extends LumynusController
{

    public function index($req,mixed $a = 'oi')
    {

        var_dump(Route::listRoutes());
    
    }
}
