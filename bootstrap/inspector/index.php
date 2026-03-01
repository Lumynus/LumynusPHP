<?php

# Lumynus Framework - A simple and lightweight PHP framework
# Copyright (C) 2025 Weleny Santos <

use Lumynus\Framework\Config;
use Lumynus\Framework\ErrorHandler;
use Lumynus\Framework\Inspector;


require_once  '../../vendor/autoload.php';

ErrorHandler::register(function () {});

if (Config::modeProduction() == false && php_sapi_name() === 'cli-server') {
    $app = new Inspector();
    $app->inspect();
    $app->renderInspectorHtml();
    return;
}

die('Inspector is disabled in production mode.');
