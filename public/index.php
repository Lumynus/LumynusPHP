<?php

# Lumynus Framework - A simple and lightweight PHP framework
# Copyright (C) 2025 Weleny Santos <

if (php_sapi_name() === 'cli-server') {
    $file = __DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if (is_file($file)) {
        return false;
    }
}

require_once __DIR__ . '/../bootstrap/useRoutes.php';
register_shutdown_function([\Lumynus\Bundle\Framework\DataBase::class, 'closeAll']);
