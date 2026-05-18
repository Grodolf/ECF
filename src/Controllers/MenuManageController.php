<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Security;
use App\Core\Session;
use App\Core\FlashMessage;
use App\Models\MenuModel;
use App\Models\DishModel;

/**
 * Handles employee menu management: listing, availability toggling, and creation.
 */
class MenuManageController extends AbstractController
{
    private MenuModel $menuModel;
    private DishModel $dishModel;

    private const ROUTE_LIST = 'employe/menus';
    private const ROUTE_CREATE = 'employe/menu/create';
    private const ROUTE_EDIT = 'employe/menu/edit/';

    public function __construct()
    {
        $this->menuModel = new MenuModel();
        $this->dishModel = new DishModel();
    }

    /**
     * Renders the menu management list for employees.
     *
     * Fetches all menus (including inactive ones), then builds two lookup maps
     * (dishes and allergens keyed by menu_id) so each menu row gets its related
     * data injected before the view is rendered.
     */
    public function list(): void
    {
        $user = Security::requireEmploye();

        $menus = $this->menuModel->findAll(false);
        $allergenes = $this->menuModel->getAllAllergenes();
        $dishes = $this->menuModel->getAllDishes();

        $allergenesByMenu = [];
        foreach ($allergenes as $allergene) {
            $menuId = $allergene['menu_id'];
            if (!isset($allergenesByMenu[$menuId])) {
                $allergenesByMenu[$menuId] = [];
            }
            $allergenesByMenu[$menuId][] =  ['id' => $allergene['id'], 'name' => $allergene['name']];
        }

        $dishesByMenu = [];
        foreach ($dishes as $dish) {
            $menuId = $dish['menu_id'];
            if (!isset($dishesByMenu[$menuId])) {
                $dishesByMenu[$menuId] = [];
            }
            $dishesByMenu[$menuId][] =  ['id' => $dish['id'], 'name' => $dish['name']];
        }

        foreach ($menus as &$menu) {
            $menuId = $menu['id'];
            $menu['dishes'] = $dishesByMenu[$menuId] ?? [];
            $menu['allergenes'] = $allergenesByMenu[$menuId] ?? [];
        }
        unset($menu);

        $this->renderView('employe/menus.php', [
            'title'        => 'Gestion des menus',
            'description'  => 'Page de gestion des menus(création, modification, désactivation)',
            'csrfToken'    => Security::generateCsrfToken(),
            'user'         => $user,
            'menus'        => $menus,
            'scripts'      => ['/js/modules/MenuToggle.js', '/js/modules/MenuRestock.js']
        ]);
    }

    /**
     * Toggles a menu's active status via AJAX.
     *
     * Requires the CSRF token in the HTTP_X_CSRF_TOKEN header.
     * Returns a JSON response with a success or error key.
     *
     * @param int $id Menu identifier.
     */
    public function toggle(int $id): void
    {
        header('Content-Type: application/json');

        Security::requireEmploye();

        $csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!Security::verifyCsrfToken($csrfToken)) {
            http_response_code(403);
            echo json_encode(['error' => FlashMessage::INVALID_CSRF]);
            exit;
        }

        if (!$this->menuModel->toggle($id)) {
            http_response_code(500);
            echo json_encode(['error' => FlashMessage::MENU_TOGGLE_ERROR]);
            exit;
        }

        echo json_encode(['success' => FlashMessage::MENU_TOGGLE_SUCCESS]);
        exit;
    }

    /**
     * Adds stock to a menu via AJAX.
     *
     * Expects a positive integer in POST quantity.
     * Returns JSON with the updated stock value on success.
     *
     * @param int $id Menu identifier.
     */
    public function addStock(int $id): void
    {
        header('Content-Type: application/json');

        Security::requireEmploye();

        $csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!Security::verifyCsrfToken($csrfToken)) {
            http_response_code(403);
            echo json_encode(['error' => FlashMessage::INVALID_CSRF]);
            exit;
        }

        $quantity = (int) $_POST['quantity'];
        if ($quantity <= 0) {
            http_response_code(400);
            echo json_encode(['error' => FlashMessage::MENU_ADDSTOCK_ERROR]);
            exit;
        }

        if (!$this->menuModel->addStock($id, $quantity)) {
            http_response_code(500);
            echo json_encode(['error' => FlashMessage::MENU_ADDSTOCK_ERROR]);
            exit;
        }

        $newStock = $this->menuModel->getStock($id);

        echo json_encode([
            'success' => FlashMessage::MENU_ADDSTOCK_SUCCESS,
            'stock' => $newStock['stock']]);
        exit;
    }

    /**
     * Renders the new-menu creation form.
     *
     * Fetches all dishes (grouped by type), available themes, and regimes
     * to populate the form selectors.
     */
    public function create(): void
    {
        $user = Security::requireEmploye();

        $dishes = $this->dishModel->findAll();
        $dishByType = [];
        foreach ($dishes as $dish) {
            $dishType = $dish['type_id'];
            $dishByType[$dishType][] = ['id' => $dish['id'], 'name' => $dish['name']];
        }

        $this->renderView('/employe/menu/create.php', [
            'title'   => 'Nouveau menu',
            'description' => "Page de création d'un nouveau menu.",
            'csrfToken'   => Security::generateCsrfToken(),
            'user'        => $user,
            'dishByType'  => $dishByType,
            'themes'      => $this->menuModel->getAllThemes(),
            'regimes'     => $this->menuModel->getAllRegimes(),
        ]);
    }

    /**
     * Handles menu creation form submission.
     *
     * Validates CSRF token and required fields, processes uploaded images,
     * persists the menu, and links its dishes and images.
     * Redirects to the menu list on success or back to the form on any error.
     */
    public function store(): void
    {
        Security::requireEmploye();

        if (!Security::verifyCsrfToken($_POST['csrf_token'])) {
            Session::setFlash(FlashMessage::INVALID_CSRF, 'error');
            $this->redirectToRoute(self::ROUTE_LIST);
            exit;
        }

        $_POST = Security::sanitizeInput($_POST);
        $fields = ['title', 'description', 'theme', 'regime', 'price', 'min_people'];
        $validate_required = Security::validateRequired($_POST, $fields);

        if ($validate_required !== []) {
            Session::setFlash(implode(', ', $validate_required), 'error');
            $this->redirectToRoute(self::ROUTE_CREATE);
            exit;
        }

        $imageUrls = [];

        if (!empty($_FILES['image']['name'])) {
            $names = (array) $_FILES['image']['name'];
            $types = (array) $_FILES['image']['type'];
            $sizes = (array) $_FILES['image']['size'];
            $tmpNames = (array) $_FILES['image']['tmp_name'];
            $errors = (array) $_FILES['image']['error'];

            $files = [];
            for ($i = 0; $i < count($names); $i++) {
                $files[] = [
                    'name'     => $names[$i],
                    'type'     => $types[$i],
                    'size'     => $sizes[$i],
                    'tmp_name' => $tmpNames[$i],
                    'error'    => $errors[$i],
                ];
            }

            $destination = dirname(__DIR__, 2) . '/public/uploads/menus/';
            $allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
            $maxSize      = 5 * 1024 * 1024;
            $extensions = [
                           'image/jpeg' => 'jpg',
                           'image/png'  => 'png',
                           'image/webp' => 'webp',
                           'image/gif'  => 'gif',
                       ];

            foreach ($files as $file) {

                if ($file['error'] !== UPLOAD_ERR_OK || $file['size'] > $maxSize) {
                    Session::setFlash(FlashMessage::MENU_IMAGE_ERROR, 'error');
                    $this->redirectToRoute(self::ROUTE_CREATE);
                    exit;
                }

                $imageInfo = getimagesize($file['tmp_name']);
                if ($imageInfo === false || !in_array($imageInfo['mime'], $allowedMimes, true)) {
                    Session::setFlash(FlashMessage::MENU_IMAGE_ERROR, 'error');
                    $this->redirectToRoute(self::ROUTE_CREATE);
                    exit;
                }

                $filename = uniqid('menu_') . '.' . $extensions[$imageInfo['mime']];

                if (!move_uploaded_file($file['tmp_name'], $destination . $filename)) {
                    Session::setFlash(FlashMessage::MENU_IMAGE_ERROR, 'error');
                    $this->redirectToRoute(self::ROUTE_CREATE);
                    exit;
                }

                $imageUrls[] = '/uploads/menus/' . $filename;
            }
        }

        $menuId = $this->menuModel->create($_POST);

        if (!$menuId) {
            Session::setFlash(FlashMessage::MENU_CREATE_ERROR, 'error');
            $this->redirectToRoute(self::ROUTE_CREATE);
            exit;
        }

        $dishes = $_POST['dishes'];
        $this->menuModel->addDishes($menuId, array_map('intval', $dishes));

        $this->menuModel->addImages($menuId, $_POST['title'], $imageUrls);

        Session::setFlash(FlashMessage::MENU_CREATED, 'success');
        $this->redirectToRoute(self::ROUTE_LIST);
        exit;
    }

    /**
     * Renders the menu edit form for a given menu.
     *
     * Redirects to the menu list with an error flash if the menu does not exist.
     *
     * @param int $id Menu identifier.
     */
    public function edit(int $id): void
    {
        $user = Security::requireEmploye();

        $menu = $this->menuModel->findById($id, false);
        if (!$menu) {
            Session::setFlash(FlashMessage::WRONG_MENU, 'error');
            $this->redirectToRoute(self::ROUTE_LIST);
            exit;
        }
        $dishes = $this->dishModel->findAll();
        $dishByType = [];
        foreach ($dishes as $dish) {
            $dishType = $dish['type_id'];
            $dishByType[$dishType][] = ['id' => $dish['id'], 'name' => $dish['name']];
        }

        $this->renderView('employe/menu/edit.php', [
            'title'       => 'Modification du menu',
            'description' => 'Page de modification de menu.',
            'user'        => $user,
            'csrfToken'   => Security::generateCsrfToken(),
            'menu'        => $menu,
            'menuDishes'  => array_column($this->menuModel->getMenuDishes($id) ?? [], 'id'),
            'images'      => $this->menuModel->getMenuImages($id),
            'themes'      => $this->menuModel->getAllThemes(),
            'regimes'     => $this->menuModel->getAllRegimes(),
            'dishByType'  => $dishByType,
            'scripts'     => ['/js/modules/DisplayOrder.js'],
        ]);
    }

    /**
     * Persists menu edits, handles image deletions, reordering, and new uploads.
     *
     * Pipeline: CSRF check → field validation → model update → image deletions
     * → image reorder → new image uploads → redirect to menu list.
     *
     * @param int $id Menu identifier.
     */
    public function update(int $id): void
    {
        Security::requireEmploye();

        if (!Security::verifyCsrfToken($_POST['csrf_token'])) {
            Session::setFlash(FlashMessage::INVALID_CSRF, 'error');
            $this->redirectToRoute(self::ROUTE_LIST);
            exit;
        }

        $_POST = Security::sanitizeInput($_POST);
        $fields = ['title', 'description', 'theme_id', 'regime_id', 'base_price', 'min_people'];
        $validate_required = Security::validateRequired($_POST, $fields);

        if ($validate_required !== []) {
            Session::setFlash(implode(', ', $validate_required), 'error');
            $this->redirectToRoute(self::ROUTE_EDIT. $id);
            exit;
        }

        $menu = $this->menuModel->update([
            'id' => $id,
            'title' => $_POST['title'],
            'description' => $_POST['description'],
            'regime_id' => $_POST['regime_id'],
            'theme_id' => $_POST['theme_id'],
            'min_people' => $_POST['min_people'],
            'base_price' => $_POST['base_price'],
            'conditions' => $_POST['conditions'],
        ]);
        if (!$menu) {
            Session::setFlash(FlashMessage::MENU_EDIT_ERROR, 'error');
            $this->redirectToRoute(self::ROUTE_EDIT. $id);
            exit;
        }

        if (isset($_POST['delete_images']) && !empty($_POST['delete_images'])) {
            $images = array_column($this->menuModel->getMenuImages($id), null, 'id');
            $toDelete = [];
            foreach ($_POST['delete_images'] as $imageId) {
                $imageId = (int) $imageId;
                $toDelete[] = [
                    'id' => $imageId,
                    'url' => $images[$imageId]['image_url']
                ];
            }
            $this->handleImageDeletions($toDelete);
        }
        if (!empty($_POST['image_id'])) {
            $this->handleImageReorder($_POST['image_id'], $_POST['display_order'], $_POST['alt_text']);
        }
        $this->handleImageUploads($id, $_POST['title'], $_FILES);

        Session::setFlash(FlashMessage::MENU_EDIT_SUCCESS, 'success');
        $this->redirectToRoute(self::ROUTE_LIST);
        exit;
    }

    /**
     * Deletes images from the database and removes their files from disk.
     *
     * @param array $images List of arrays with 'id' (DB row) and 'url' (public path).
     */
    private function handleImageDeletions(array $images): void
    {
        foreach ($images as $image) {
            $this->menuModel->deleteImage($image['id']);
            $filePath = dirname(__DIR__, 2) . '/public' . $image['url'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }
    }

    /**
     * Updates display_order and alt_text for existing menu images.
     *
     * Skips the update silently when the three arrays have mismatched lengths.
     *
     * @param array $ids      Image IDs in the new order.
     * @param array $orders   New display_order values, indexed by position.
     * @param array $altTexts New alt_text values, indexed by position.
     */
    private function handleImageReorder(array $ids, array $orders, array $altTexts): void
    {
        $images = [];

        if (count($ids) === count($orders) && count($ids) === count($altTexts)) {

            $i = 0;
            for ($i; $i < count($ids); $i++) {
                $images[] = [
                    'id'            => $ids[$i],
                    'display_order' => $orders[$i],
                    'alt_text'      => $altTexts[$i],
                ];
            }
        }
        $this->menuModel->updateImageOrder($images);
    }

    /**
     * Validates and moves newly uploaded menu images, then persists their URLs.
     *
     * Accepts only JPEG, PNG, WebP, and GIF files up to 5 MB (verified via getimagesize).
     * Redirects with an error flash if any file fails validation or the move fails.
     *
     * @param int    $id    Menu identifier.
     * @param string $title Menu title used to generate alt text.
     * @param array  $files The $_FILES superglobal (or a slice of it).
     */
    private function handleImageUploads(int $id, string $title, array $files): void
    {
        $imageUrls = [];

        if (!empty($files['images']['name'][0])) {
            $names = (array) $files['images']['name'];
            $types = (array) $files['images']['type'];
            $sizes = (array) $files['images']['size'];
            $tmpNames = (array) $files['images']['tmp_name'];
            $errors = (array) $files['images']['error'];

            $images = [];
            for ($i = 0; $i < count($names); $i++) {
                $images[] = [
                    'name'     => $names[$i],
                    'type'     => $types[$i],
                    'size'     => $sizes[$i],
                    'tmp_name' => $tmpNames[$i],
                    'error'    => $errors[$i],
                ];
            }

            $destination = dirname(__DIR__, 2) . '/public/uploads/menus/';
            $allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
            $maxSize      = 5 * 1024 * 1024;
            $extensions = [
                           'image/jpeg' => 'jpg',
                           'image/png'  => 'png',
                           'image/webp' => 'webp',
                           'image/gif'  => 'gif',
                       ];

            foreach ($images as $image) {

                if ($image['error'] !== UPLOAD_ERR_OK || $image['size'] > $maxSize) {
                    Session::setFlash(FlashMessage::MENU_IMAGE_ERROR, 'error');
                    $this->redirectToRoute(self::ROUTE_EDIT. $id);
                    exit;
                }

                $imageInfo = getimagesize($image['tmp_name']);
                if ($imageInfo === false || !in_array($imageInfo['mime'], $allowedMimes, true)) {
                    Session::setFlash(FlashMessage::MENU_IMAGE_ERROR, 'error');
                    $this->redirectToRoute(self::ROUTE_EDIT. $id);
                    exit;
                }

                $filename = uniqid('menu_') . '.' . $extensions[$imageInfo['mime']];

                if (!move_uploaded_file($image['tmp_name'], $destination . $filename)) {
                    Session::setFlash(FlashMessage::MENU_IMAGE_ERROR, 'error');
                    $this->redirectToRoute(self::ROUTE_EDIT. $id);
                    exit;
                }

                $imageUrls[] = '/uploads/menus/' . $filename;
            }
        }

        $this->menuModel->addImages($id, $title, $imageUrls);
    }
}
