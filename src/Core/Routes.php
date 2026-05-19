<?php

declare(strict_types=1);

namespace App\Core;

use App\Controllers\MainController;
use App\Controllers\AuthController;
use App\Controllers\MenuController;
use App\Controllers\UserController;
use App\Controllers\OrderController;
use App\Controllers\EmployeController;
use App\Controllers\DishController;
use App\Controllers\MenuManageController;
use App\Controllers\ReviewController;
use App\Controllers\AdminController;

/**
 * Application routing table.
 *
 * The ROUTES constant maps each URL path to a controller class and method.
 * Static routes must be declared before dynamic ones to prevent collisions,
 * as the Router iterates the array in order.
 *
 * Dynamic parameter syntax: {paramName} (e.g. 'order/{menuId}').
 */
class Routes
{
    public const ROUTES = [
        'home' => [
            'controller' => MainController::class,
            'method'     => 'home',
            'http'       => 'GET',
        ],
        'contact' => [
            'controller' => MainController::class,
            'method'     => 'contact',
            'http'       => 'GET',
        ],
        'sendmail' => [
            'controller' => MainController::class,
            'method'     => 'sendmail',
            'http'       => 'POST'
        ],
        'mentions-legales' => [
            'controller' => MainController::class,
            'method'     => 'legalNotice',
            'http'       => 'GET'
        ],
        'cgv' => [
            'controller' => MainController::class,
            'method'     => 'cgv',
            'http'       => 'GET'
        ],
        'login' => [
            'controller' => AuthController::class,
            'method'     => 'login',
            'http'       => ['GET', 'POST'],
        ],
        'logout' => [
            'controller' => AuthController::class,
            'method'     => 'logout',
            'http'       => 'GET',
        ],
        'register' => [
            'controller' => AuthController::class,
            'method'     => 'register',
            'http'       => ['GET', 'POST'],
        ],
        'reset-password' => [
            'controller' => AuthController::class,
            'method'     => 'resetPassword',
            'http'       => ['GET', 'POST'],
        ],
        'new-password/{token}' => [
            'controller' => AuthController::class,
            'method'     => 'newPassword',
            'http'       => ['GET', 'POST'],
        ],
        'profile' => [
            'controller' => UserController::class,
            'method'     => 'profile',
            'http'       => 'GET',
        ],
        'edit-profile' => [
            'controller' => UserController::class,
            'method'     => 'editProfile',
            'http'       => ['GET', 'POST'],
        ],
        'change-password' => [
            'controller' => UserController::class,
            'method'     => 'changePassword',
            'http'       => ['GET', 'POST'],
        ],
        'menus' => [
            'controller' => MenuController::class,
            'method'     => 'list',
            'http'       => 'GET',
        ],
        'menu/{id}' => [
            'controller' => MenuController::class,
            'method'     => 'detail',
            'http'       => 'GET',
        ],
        'menus/filter' => [
            'controller' => MenuController::class,
            'method'     => 'filter',
            'http'       => 'POST',
        ],
        'order/store' => [
            'controller' => OrderController::class,
            'method'     => 'store',
            'http'       => 'POST',
        ],
        'order/calculate-price' => [
            'controller' => OrderController::class,
            'method'     => 'calculatePrice',
            'http'       => 'POST',
        ],
        'order/confirmation/{orderId}' => [
            'controller' => OrderController::class,
            'method'     => 'confirmation',
            'http'       => 'GET',
        ],
        'order/detail/{orderId}' => [
            'controller' => OrderController::class,
            'method' => 'show',
            'http' => 'GET'
        ],
        'order/edit/{orderId}' => [
            'controller' => OrderController::class,
            'method'     => 'edit',
            'http'       => ['GET', 'POST'],
        ],
        'order/cancel/{orderId}' => [
            'controller' => OrderController::class,
            'method' => 'cancel',
            'http' => 'POST'
        ],
        'orders'          => [
            'controller' => OrderController::class,
            'method' => 'list',
            'http' => 'GET'
        ],
        'order' => [
            'controller' => OrderController::class,
            'method'     => 'create',
            'http'       => 'GET'
        ],
        'order/{menuId}' => [
            'controller' => OrderController::class,
            'method'     => 'create',
            'http'       => 'GET',
        ],
        'employe/order/update-status/{orderId}' => [
            'controller' => EmployeController::class,
            'method'     => 'updateStatus',
            'http'       => 'POST',
        ],
        'employe/order/cancel/{orderId}' => [
            'controller' => EmployeController::class,
            'method'     => 'cancelOrder',
            'http'       => 'POST',
        ],
        'employe/orders' => [
            'controller' => EmployeController::class,
            'method'     => 'orders',
            'http'       => 'GET',
        ],
        'employe/dishes' => [
            'controller' => DishController::class,
            'method'     => 'list',
            'http'       => 'GET',
        ],
        'employe/dish/create' => [
            'controller' => DishController::class,
            'method'     => 'create',
            'http'       => 'GET',
        ],
        'employe/dish/store' => [
            'controller' => DishController::class,
            'method'     => 'store',
            'http'       => 'POST',
        ],
        'employe/dish/edit/{id}' => [
            'controller' => DishController::class,
            'method'     => 'edit',
            'http'       => 'GET',
        ],
        'employe/dish/update/{id}' => [
            'controller' => DishController::class,
            'method'     => 'update',
            'http'       => 'POST',
        ],
        'employe/dish/toggle/{id}' => [
            'controller' => DishController::class,
            'method'     => 'toggle',
            'http'       => 'POST',
        ],
        'employe/menus' => [
            'controller' => MenuManageController::class,
            'method'     => 'list',
            'http'       => 'GET'
        ],
        'employe/menu/create' => [
            'controller' => MenuManageController::class,
            'method'     => 'create',
            'http'       => 'GET'
        ],
        'employe/menu/store' => [
            'controller' => MenuManageController::class,
            'method'     => 'store',
            'http'       => 'POST'
        ],
        'employe/menu/edit/{id}' => [
            'controller' => MenuManageController::class,
            'method'     => 'edit',
            'http'       => 'GET'
        ],
        'employe/menu/update/{id}' => [
            'controller' => MenuManageController::class,
            'method'     => 'update',
            'http'       => 'POST'
        ],
        'employe/menu/toggle/{id}' => [
            'controller' => MenuManageController::class,
            'method'     => 'toggle',
            'http'       => 'POST'
        ],
        'employe/menu/addstock/{id}' => [
            'controller' => MenuManageController::class,
            'method'     => 'addstock',
            'http'       => 'POST'
        ],
        'employe/schedules' => [
            'controller' => EmployeController::class,
            'method'     => 'schedules',
            'http'       => 'GET'
        ],
        'employe/schedule/update' => [
            'controller' => EmployeController::class,
            'method'     => 'updateSchedules',
            'http'       => 'POST'
        ],
        'employe/reviews' => [
            'controller' => ReviewController::class,
            'method'     => 'list',
            'http'       => 'GET'
        ],
        'review/store/{id}' => [
            'controller' => ReviewController::class,
            'method'     => 'store',
            'http'       => 'POST'
        ],
        'review/validate/{id}' => [
            'controller' => ReviewController::class,
            'method'     => 'valid',
            'http'       => 'POST'
        ],
        'review/{id}' => [
            'controller' => ReviewController::class,
            'method'     => 'create',
            'http'       => 'GET'
        ],
        'admin/employes' => [
            'controller' => AdminController::class,
            'method'     => 'list',
            'http'       => 'GET'
        ],
        'admin/employe/create' => [
            'controller' => AdminController::class,
            'method'     => 'create',
            'http'       => 'GET'
        ],
        'admin/employe/store' => [
            'controller' => AdminController::class,
            'method'     => 'store',
            'http'       => 'POST'
        ],
        'admin/employe/confirmation' => [
            'controller' => AdminController::class,
            'method'     => 'confirmation',
            'http'       => 'GET'
        ],
        'admin/employe/toggle/{id}' => [
            'controller' => AdminController::class,
            'method'     => 'toggle',
            'http'       => 'POST'
        ],
        'admin/stats' => [
            'controller' => AdminController::class,
            'method'     => 'stats',
            'http'       => 'GET'
        ]
    ];
}
