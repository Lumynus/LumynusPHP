<?php

# Lumynus Framework - A simple and lightweight PHP framework
# Copyright (C) 2025 Weleny Santos <

use Lumynus\Bundle\Framework\Route;
use Lumynus\Bundle\Framework\ErrorHandler;


require_once __DIR__ . '/../vendor/autoload.php';

ErrorHandler::register(function(){});
Route::start();
