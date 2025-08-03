<?php

use App\Controllers\ControllerExample;
use Lumynus\Bundle\Framework\Route;

// Route::get('test[bool *, string oi]', ControllerExample::class, 'index');    #####Vários campos com tipos
// Route::get('test[string *]', ControllerExample::class, 'index');    #####Vários campos sem tipos
// Route::get(
//     [
//         'test[string e]',
//         'teste[string oi]'
//     ],
//     ControllerExample::class,
//     'index'
// );


// Route::get('teste[string e]', ControllerExample::class, 'index');

// Route::midd([\App\Middlewares\Teste::class, \App\Middlewares\Oi::class], ['handle','handle'], function () {
//     Route::get('teste[string e]', ControllerExample::class, 'index');
// });

Route::get('test[string e, string v]', ControllerExample::class, 'index');
Route::get('va[string e]', ControllerExample::class, 'teste3');
