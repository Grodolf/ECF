<?php

declare(strict_types=1);

namespace App\Core;

use App\Controllers\MainController;
use App\Controllers\AuthController;
use App\Controllers\UserController;

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
        'login' => [
            'controller' => AuthController::class,
            'method' => 'login'
        ],
        'logout' => [
            'controller' => AuthController::class,
            'method' => 'logout'
        ],
        'register' => [
            'controller' => AuthController::class,
            'method' => 'register'
        ],
        'reset-password' => [
            'controller' => AuthController::class,
            'method' => 'resetPassword'
        ],
        'new-password/{token}' => [
            'controller' => AuthController::class,
            'method' => 'newPassword'
        ],
        'profile' => [
            'controller' => UserController::class,
            'method' => 'profile'
        ],
        'edit-profile' => [
            'controller' => UserController::class,
            'method' => 'editProfile'
        ],
        'change-password' => [
            'controller' => UserController::class,
            'method' => 'changePassword'
        ],
    ];
}
