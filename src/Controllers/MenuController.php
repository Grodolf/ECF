<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\FlashMessage;
use App\Core\Security;
use App\Core\Session;
use App\Models\MenuModel;

/**
 * Handles menu browsing: list page, detail page, and AJAX filtering.
 */
class MenuController extends AbstractController
{
    private MenuModel $menuModel;
    private const ROUTE_MENUS = 'menus';
    public const PLACEHOLDER  = '/img/placeholder.webp';

    public function __construct()
    {
        $this->menuModel = new MenuModel();
    }

    /**
     * Attaches image data to each menu, falling back to a placeholder when none exists.
     *
     * Builds a lookup map keyed by menu_id so the merge is O(n) instead of O(n²).
     *
     * @param array $menus  Flat menu rows from the database.
     * @param array $images Image rows with menu_id, image_url, and alt_text fields.
     * @return array Menu rows with src and alt keys added.
     */
    private function imagesByMenuId(array $menus, array $images): array
    {
        $imagesByMenuId = [];

        foreach ($images as $image) {
            $imagesByMenuId[$image['menu_id']] = $image;
        }

        foreach ($menus as &$menu) {
            if (isset($imagesByMenuId[$menu['id']])) {
                $menu['src'] = $imagesByMenuId[$menu['id']]['image_url'];
                $menu['alt'] = $imagesByMenuId[$menu['id']]['alt_text'];
            } else {
                $menu['src'] = self::PLACEHOLDER;
                $menu['alt'] = 'Menu ' . $menu['title'];
            }
        }
        unset($menu);

        return $menus;
    }

    /**
     * Renders the full menu list with their cover images.
     */
    public function list(): void
    {
        $menus = $this->menuModel->findAll();
        $images = $this->menuModel->getListImages();
        $menus = $this->imagesByMenuId($menus, $images);

        $this->renderView('menus/list.php', [
            'title' => 'Découvrez nos&nbsp;menus',
            'description' => 'Vous trouverez l\'ensemble de nos menus, à base de produits frais et de saison.',
            'menus' => $menus,
            'scripts' => ['/js/modules/MenuFilter.js']
        ]);
    }

    /**
     * Renders the detail page for a single menu.
     *
     * Fetches the menu, its dishes, and their allergens. Dishes are grouped by
     * type (dish_type_name) and each dish gets its allergen list injected before
     * the view is rendered. Redirects to the menu list if the menu does not exist.
     *
     * @param int $id Menu identifier.
     */
    public function detail(int $id): void
    {

        $menu = $this->menuModel->findById($id);

        if (!$menu) {
            Session::setFlash(FlashMessage::WRONG_MENU, 'error');
            $this->redirectToRoute(self::ROUTE_MENUS);
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

        $dishesByType = [];
        foreach ($dishes as $dish) {
            $type = $dish['dish_type_name'];
            $dish['allergenes'] = $allergenesByDish[$dish['id']] ?? [];
            if (empty($dish['image_url'])) {
                $dish['image_url'] = self::PLACEHOLDER;
                $dish['alt_text'] = 'Image manquante';
            }
            if (!isset($dishesByType[$type])) {
                $dishesByType[$type] = [];
            }
            $dishesByType[$type][] = $dish;
        }

        $this->renderView('menus/detail.php', [
            'title' => $menu['title'],
            'description' => $menu['description'],
            'menu' => $menu,
            'images' => $this->menuModel->getMenuImages($id),
            'dishesByType' => $dishesByType,
            'scripts' => ['/js/modules/Carousel.js']
        ]);
    }

    /**
     * AJAX endpoint that returns filtered menus with stats and available filter options.
     *
     * Requires an XMLHttpRequest with a CSRF token in the HTTP_X_CSRF_TOKEN header.
     * Accepts POST fields: min_price, max_price, min_people, theme, regime.
     * Returns a JSON object with:
     * - menus: filtered menu rows with cover images attached
     * - stats: count, min/max price and min/max people across the result set
     * - available_options: themes and regimes still selectable given the current price/people filters
     */
    public function filter(): void
    {
        $csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!Security::verifyCsrfToken($csrfToken)) {
            http_response_code(403);
            echo json_encode(['error' => FlashMessage::INVALID_CSRF]);
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
        $menus = $this->imagesByMenuId($menus, $images);

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
