<?php

return [
    'paths' => [
        'controllers' => 'Http/Controllers',
        'interfaces' => 'Interfaces', 
        'repositories' => 'Repositories',
        'routes' => 'Http/Routes',
    ],

    'namespaces' => [
        'controllers' => 'App\Http\Controllers',
        'interfaces' => 'App\Interfaces',
        'repositories' => 'App\Repositories', 
        'routes' => 'App\Http\Routes',
    ],

    'templates' => [
        'controller_extends' => 'Controller',
        'repository_implements_interface' => true,
        'use_dependency_injection' => true,
        'generate_route_file' => true,
    ],
];