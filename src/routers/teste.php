<?php

use App\Controllers\ControllerExample;
use Lumynus\Bundle\Framework\Route;

Route::midd([App\Middlewares\Teste::class], 'handle', function () {
    Route::get(['teste/{mensage}[string]?[string va]', 'test?[string a]'], ControllerExample::class, 'index');
});

Route::get('testy?[string a]', ControllerExample::class, 'index');

