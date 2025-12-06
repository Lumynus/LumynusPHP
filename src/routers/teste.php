<?php

use App\Controllers\ControllerExample;
use Lumynus\Bundle\Framework\Route;


Route::get(['teste/{mensage}[string]?[string va]','test/{mensage}[int]'], ControllerExample::class, 'index');
