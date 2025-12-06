<?php

return array (
  'static' => 
  array (
  ),
  'dynamic' => 
  array (
    'GET' => 
    array (
      '~^teste/(?P<mensage>[^/]+)$~' => 
      array (
        'controller' => 'App\\Controllers\\ControllerExample',
        'action' => 'index',
        'fieldsPermitted' => 
        array (
          'va' => 'string',
          'mensage' => 'string',
        ),
        'middlewares' => 
        array (
        ),
        'api' => false,
      ),
      '~^test/(?P<mensage>[^/]+)$~' => 
      array (
        'controller' => 'App\\Controllers\\ControllerExample',
        'action' => 'index',
        'fieldsPermitted' => 
        array (
          'mensage' => 'int',
        ),
        'middlewares' => 
        array (
        ),
        'api' => false,
      ),
    ),
  ),
);