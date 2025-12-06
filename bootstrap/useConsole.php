<?php

# Lumynus Framework - A simple and lightweight PHP framework
# Copyright (C) 2025 Weleny Santos

use Lumynus\Bundle\Framework\Logs;
use Lumynus\Bundle\Framework\LumaConsole;

require_once __DIR__ . '/../vendor/autoload.php';

error_reporting(E_ALL);
ini_set('display_errors', 0);

set_error_handler(function($errno, $errstr, $errfile, $errline) {
    Logs::register($errstr, $errfile . ' ' . $errline);
    return true;
});

register_shutdown_function(function() {
    $erro = error_get_last();
    if ($erro) {
        Logs::register($erro['message'], $erro['file'] . ' ' . $erro['line']);
    }
});

LumaConsole::run($argv);
