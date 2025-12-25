<?php

declare(strict_types=1);

namespace App\Controllers;

use Lumynus\Bundle\Framework\LumynusController;

class ControllerExample extends LumynusController
{
    public function index($request)
    {
        $this->response()->json(['success' => true, 'data' => 'Em pleno funcionamento']);

    }
}
