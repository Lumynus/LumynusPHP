<?php

return array (
  'static' => 
  array (
    'GET' => 
    array (
      'test' => 
      array (
        'controller' => 'App\\Controllers\\ControllerExample',
        'action' => 'index',
        'fieldsPermitted' => 
        array (
          'a' => 'string',
        ),
        'middlewares' => 
        array (
          0 => 
          array (
            'midd' => 'App\\Middlewares\\Teste',
            'action' => 'handle',
          ),
        ),
        'api' => false,
      ),
      'testy' => 
      array (
        'controller' => 'App\\Controllers\\ControllerExample',
        'action' => 'index',
        'fieldsPermitted' => 
        array (
          'a' => 'string',
        ),
        'middlewares' => 
        array (
        ),
        'api' => false,
      ),
    ),
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
          0 => 
          array (
            'midd' => 'App\\Middlewares\\Teste',
            'action' => 'handle',
          ),
        ),
        'api' => false,
      ),
    ),
  ),
);