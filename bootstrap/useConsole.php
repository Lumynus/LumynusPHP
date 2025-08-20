<?php

# Lumynus Framework - A simple and lightweight PHP framework
# Copyright (C) 2025 Weleny Santos <

use Lumynus\Bundle\Framework\LumaConsole;

require_once __DIR__ . '/../vendor/autoload.php';

ini_set('display_errors', '0');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
LumaConsole::run($argv);
