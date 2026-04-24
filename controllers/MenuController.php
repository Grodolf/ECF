<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\AbstractController;
use App\Core\FlashMessage;
use App\Models\MenuModel;

class MenuController extends AbstractController
{
    private MenuModel $menuModel;

    public function __construct()
    {
        $this->menuModel = new MenuModel();
        define('PLACEHOLDER', '/img/placeholder.webp');
    }

    public function list()
    {
        $menus = $this->menuModel->findAll();
        $images = $this->menuModel->getListImages();

        $imagesByMenuId = [];
        foreach ($images as $image) {
            $imagesByMenuId[$image['menu_id']] = $image;
        }

        foreach ($menus as &$menu) {
            if (isset($imagesByMenuId[$menu['id']])) {
                $menu['src'] = $imagesByMenuId[$menu['id']]['image_url'];
                $menu['alt'] = $imagesByMenuId[$menu['id']]['alt_text'];
            } else {
                $menu['src'] = PLACEHOLDER;
                $menu['alt'] = 'Menu ' . $menu['title'];
            }
        }
        unset($menu);

        $this->renderView('menus/list.php', [
            'title' => 'Découvrez nos&nbsp;menus',
            'description' => 'Vous trouvererez l\'ensemble de nos menus, à base de produits frais et de saison.',
            'menus' => $menus,
            'scripts' => ['/js/modules/MenuFilter.js']
        ]);
    }

    public function detail(int $id)
    {

        $menu = $this->menuModel->findById($id);

        if (empty($menu)) {
            FlashMessage::wrongMenu();
            $this->redirectToRoute('list');
            die;
        }

        $dishes = $this->menuModel->getMenuDishes($id);
        $allergenes = $this->menuModel->getAllergenesForMenu($id);

        $allergenesByDish = [];
        foreach ($allergenes as $allergene) {
            $dishId = $allergene['dish_id'];
            if (!isset($allergenesByDish[$dishId])) {
                $allergenesByDish[$dishId] = [];
            }
            $allergenesByDish[$dishId][] = [
                'name' => $allergene['allergene_name'],
                'description' => $allergene['allergene_description']
            ];
        }
        unset($allergene);

        $dishesByType = [];
        foreach ($dishes as $dish) {
            $type = $dish['dish_type_name'];
            $dish['allergenes'] = $allergenesByDish[$dish['id']] ?? [];
            if (empty($dish['image_url'])) {
                $dish['image_url'] = PLACEHOLDER;
                $dish['alt_text'] = 'Image manquante';
            }
            if (!isset($dishesByType[$type])) {
                $dishesByType[$type] = [];
            }
            array_push($dishesByType[$type], $dish);
        }
        unset($dish);

        $this->renderView('menus/detail.php', [
            'title' => $menu['title'],
            'description' => $menu['description'],
            'menu' => $menu,
            'images' => $this->menuModel->getMenuImages($id),
            'dishesByType' => $dishesByType,
            'scripts' => ['/js/modules/Carousel.js']
        ]);
    }

    public function filter()
    {
        if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) ||
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
            http_response_code(400);
            echo json_encode(['error' => 'Bad request']);
            exit;
        }

        $filters = [
            'min_price' => $_POST['min_price'] ?? '',
            'max_price' => $_POST['max_price'] ?? '',
            'min_people' => $_POST['min_people'] ?? '',
            'theme' => $_POST['theme'] ?? '',
            'regime' => $_POST['regime'] ?? ''
        ];

        $menus = $this->menuModel->findFiltered($filters);

        $filtersForOptions = [
            'min_price' => $filters['min_price'],
            'max_price' => $filters['max_price'],
            'min_people' => $filters['min_people']
        ];
        $availableOptions = $this->menuModel->getAvailableOptions($filtersForOptions);

        $images = $this->menuModel->getListImages();
        $imagesByMenuId = [];
        foreach ($images as $image) {
            $imagesByMenuId[$image['menu_id']] = $image;
        }

        foreach ($menus as &$menu) {
            if (isset($imagesByMenuId[$menu['id']])) {
                $menu['src'] = $imagesByMenuId[$menu['id']]['image_url'];
                $menu['alt'] = $imagesByMenuId[$menu['id']]['alt_text'];
            } else {
                $menu['src'] = PLACEHOLDER;
                $menu['alt'] = 'Menu ' . $menu['title'];
            }
        }
        unset($menu);

        $stats = [
        'count' => count($menus),
        'min_price' => null,
        'max_price' => null,
        'min_people' => null,
        'max_people' => null
    ];

        if (count($menus) > 0) {
            $prices = array_column($menus, 'base_price');
            $people = array_column($menus, 'min_people');

            $stats['min_price'] = min($prices);
            $stats['max_price'] = max($prices);
            $stats['min_people'] = min($people);
            $stats['max_people'] = max($people);
        }

        header('Content-Type: application/json');
        echo json_encode([
            'menus' => $menus,
            'stats' => $stats,
            'available_options' => $availableOptions
        ]);
        exit;
    }
}
