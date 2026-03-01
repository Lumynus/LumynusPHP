<?php

# Lumynus Framework - A simple and lightweight PHP framework
# Copyright (C) 2025 Weleny Santos <

use Lumynus\Http\HttpKernel;
use Lumynus\Framework\ErrorHandler;

require_once __DIR__ . '/../vendor/autoload.php';

ErrorHandler::register(function () {});

$kernel = new HttpKernel();
$kernel->handle();
