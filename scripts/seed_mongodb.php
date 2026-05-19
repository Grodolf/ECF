<?php

require_once __DIR__ . '/../vendor/autoload.php';
App\Core\Env::loadEnv(__DIR__ . '/../config/');

use App\Core\MongoDBConnection;
use App\Models\MenuModel;

$mongodb = MongoDBConnection::getInstance();
$menuModel = new MenuModel();

$menus = $menuModel->findAll();
$menus = array_column($menus, null, 'id');

$distribution = [
    ['year' => 2025,
    'month' => 12,
    'sales' => [
        '1' => 8,
        '2' => 4,
        '3' => 0,
        '4' => 6,
        '5' => 0,
        '6' => 1,
    ]],
    ['year' => 2026,
    'month' => 01,
    'sales' => [
        '1' => 1,
        '2' => 0,
        '3' => 0,
        '4' => 5,
        '5' => 0,
        '6' => 2,
    ]],
    ['year' => 2026,
    'month' => 02,
    'sales' => [
        '1' => 0,
        '2' => 1,
        '3' => 3,
        '4' => 5,
        '5' => 0,
        '6' => 1,
    ]],
    ['year' => 2026,
    'month' => 03,
    'sales' => [
        '1' => 0,
        '2' => 0,
        '3' => 6,
        '4' => 3,
        '5' => 1,
        '6' => 0,
    ]],
    ['year' => 2026,
    'month' => 04,
    'sales' => [
        '1' => 0,
        '2' => 2,
        '3' => 5,
        '4' => 5,
        '5' => 3,
        '6' => 1,
    ]],
    ['year' => 2026,
    'month' => 05,
    'sales' => [
        '1' => 0,
        '2' => 0,
        '3' => 4,
        '4' => 4,
        '5' => 3,
        '6' => 2,
    ]],
];

$menu_sales = [];

foreach ($distribution as $month) {
    $menu_sale['month'] = $month['month'];
    $menu_sale['year'] = $month['year'];
    $menu_sale['sales'] = [];
    foreach ($month['sales'] as $id => $sale) {
        if ($sale === 0) {
            continue;
        }
        $people = rand($menus[$id]['min_people'], $menus[$id]['min_people'] + 10);
        $menu_sale['sales'][] = [
            'menu_id'     => $menus[$id]['id'],
            'title'       => $menus[$id]['title'],
            'people'      => $people,
            'total_price' => totalPrice($people, $menus[$id]['min_people'], $menus[$id]['base_price'])
        ];
    }
    $menu_sales[] = $menu_sale;
}

function totalPrice(int $people, int $min_people, float $price): float
{
    $total = $price * $people;
    if ($people >= $min_people + 5) {
        $total = $total * 0.9;
    }
    return $total;
}

$mongodb->createCollection('sales');
$collection = $mongodb->selectCollection('sales');
$collection->insertMany($menu_sales);
