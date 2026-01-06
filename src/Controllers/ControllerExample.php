<?php

declare(strict_types=1);

namespace App\Controllers;

use Lumynus\Bundle\Framework\LumynusController;
use Lumynus\Http\Contracts\Request;
use Lumynus\Http\Contracts\Response;

class ControllerExample extends LumynusController
{
    public function index(Response $res, Request $req): Response
    {

        return $res->status(200)->json(["Ok"]);
    }
}
