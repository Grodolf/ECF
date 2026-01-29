<?php

declare(strict_types=1);

namespace App\Core;

use App\Controllers\MainController;

class Routes
{
    public const ROUTES = [
        '/' => [
            'controller' => MainController::class,
            'method' => 'home'
        ],
        'home' => [
            'controller' => MainController::class,
            'method' => 'home'
        ],
        'contact' => [
            'controller' => MainController::class,
            'method' => 'contact'
        ],
        'test-db' => [
            'controller' => MainController::class,
            'method' => 'testDatabase'
        ]
    ];
}
