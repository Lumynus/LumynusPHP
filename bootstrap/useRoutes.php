<?php

use Lumynus\Bundle\Framework\Route;
use Lumynus\Bundle\Framework\ErrorHandler;


require_once __DIR__ . '/../vendor/autoload.php';

ErrorHandler::register(function(){});
Route::start();
