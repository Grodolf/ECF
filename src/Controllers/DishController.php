<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Security;
use App\Core\Session;
use App\Core\FlashMessage;
use App\Models\DishModel;

/**
 * Handles employee dish management: listing, creation, editing, and availability toggling.
 */
class DishController extends AbstractController
{
    private const ROUTE_LIST = 'employe/dishes';
    private const ROUTE_CREATE = 'employe/dish/create';
    private const ROUTE_EDIT = 'employe/dish/edit';
    public const PLACEHOLDER  = '/img/placeholder.webp';

    private DishModel $dishModel;

    public function __construct()
    {
        $this->dishModel = new DishModel();
    }

    /**
     * Renders the dish management list for employees.
     *
     * Fetches all dishes and builds an allergen lookup map keyed by dish_id
     * so each dish row gets its related allergens injected before rendering.
     */
    public function list()
    {
        $user = Security::requireEmploye();

        $dishes = $this->dishModel->findAll();
        $allergenes = $this->dishModel->getAllergenesForDish();

        $allergenesByDish = [];
        foreach ($allergenes as $allergene) {
            $dishId = $allergene['dish_id'];
            if (!isset($allergenesByDish[$dishId])) {
                $allergenesByDish[$dishId] = [];
            }
            $allergenesByDish[$dishId][] =  $allergene['name'];
        }

        $dishesByType = [];
        foreach ($dishes as $dish) {
            $dish['allergenes'] = $allergenesByDish[$dish['id']] ?? [];
            $dishesByType[] = $dish;
        }

        $this->renderView('employe/dishes.php', [
            'title'        => 'Gestion des plats',
            'description'  => 'Page de gestion des plats(création, modification, désactivation)',
            'user'         => $user,
            'dishes'       => $dishesByType,
            'csrfToken'    => Security::generateCsrfToken(),
            'scripts'      => ['/js/modules/DishToggle.js']
        ]);
    }

    /**
     * Renders the new-dish creation form.
     *
     * Fetches all dish types and allergens to populate the form selectors.
     */
    public function create(): void
    {
        $user = Security::requireEmploye();

        $types = $this->dishModel->getAllDishTypes();
        $allergenes = $this->dishModel->getAllAllergenes();

        $this->renderView('employe/dish/create.php', [
            'title'        => 'Nouveau plat',
            'description'  => "Page de création d'un nouveau plat",
            'user'         => $user,
            'types'        => $types,
            'allergenes'   => $allergenes,
            'csrfToken'    => Security::generateCsrfToken(),
        ]);
    }

    /**
     * Persists a new dish submitted via the creation form.
     *
     * Pipeline: CSRF check → field validation → optional image upload (5 MB max,
     * MIME verified with getimagesize) → DB insert → allergen pivot insert → redirect.
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
        $fields = ['name', 'description', 'type'];
        $validate_required = Security::validateRequired($_POST, $fields);

        if ($validate_required !== []) {
            Session::setFlash(implode(', ', $validate_required), 'error');
            $this->redirectToRoute(self::ROUTE_CREATE);
            exit;
        }

        $imageUrl = null;

        if (!empty($_FILES['image']['name'])) {
            $file        = $_FILES['image'];
            $destination = dirname(__DIR__, 2) . '/public/uploads/dishes/';
            $allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
            $maxSize      = 5 * 1024 * 1024;

            if ($file['error'] !== UPLOAD_ERR_OK || $file['size'] > $maxSize) {
                Session::setFlash(FlashMessage::DISH_IMAGE_ERROR, 'error');
                $this->redirectToRoute(self::ROUTE_CREATE);
                exit;
            }

            $imageInfo = getimagesize($file['tmp_name']);
            if ($imageInfo === false || !in_array($imageInfo['mime'], $allowedMimes, true)) {
                Session::setFlash(FlashMessage::DISH_IMAGE_ERROR, 'error');
                $this->redirectToRoute(self::ROUTE_CREATE);
                exit;
            }

            $extensions = [
                'image/jpeg' => 'jpg',
                'image/png'  => 'png',
                'image/webp' => 'webp',
                'image/gif'  => 'gif',
            ];
            $filename = uniqid('dish_') . '.' . $extensions[$imageInfo['mime']];

            if (!move_uploaded_file($file['tmp_name'], $destination . $filename)) {
                Session::setFlash(FlashMessage::DISH_IMAGE_ERROR, 'error');
                $this->redirectToRoute(self::ROUTE_CREATE);
                exit;
            }

            $imageUrl = '/uploads/dishes/' . $filename;
        }

        $dishId = $this->dishModel->create([
            'name'        => $_POST['name'],
            'description' => $_POST['description'],
            'type'        => $_POST['type'],
            'url'         => $imageUrl,
        ]);

        if (!$dishId) {
            Session::setFlash(FlashMessage::DISH_CREATE_ERROR, 'error');
            $this->redirectToRoute(self::ROUTE_CREATE);
            exit;
        }

        $allergenes = $_POST['allergenes'] ?? [];
        if (!empty($allergenes)) {
            $this->dishModel->addAllergenes($dishId, array_map('intval', $allergenes));
        }

        Session::setFlash(FlashMessage::DISH_CREATED, 'success');
        $this->redirectToRoute(self::ROUTE_LIST);
        exit;
    }

    /**
     * Renders the dish edit form pre-filled with existing data.
     *
     * Fetches the dish, its current allergens (and their IDs for pre-selection),
     * and all available allergens. Redirects to the list if the dish does not exist.
     *
     * @param int $id Dish identifier.
     */
    public function edit(int $id): void
    {
        $user = Security::requireEmploye();

        $dish = $this->dishModel->findById($id);
        if (!$dish) {
            Session::setFlash(FlashMessage::WRONG_DISH, 'error');
            $this->redirectToRoute(self::ROUTE_LIST);
            exit;
        }
        $dish['allergenes'] = $this->dishModel->getAllergenesByDishId($id);
        $dish['allergene_ids'] = array_column($dish['allergenes'], 'id');
        $allergenes = $this->dishModel->getAllAllergenes();

        $this->renderView('employe/dish/edit.php', [
            'title'        => 'Modification',
            'description'  => "Page de modification d'un plat",
            'user'         => $user,
            'dish'         => $dish,
            'allergenes'   => $allergenes,
            'csrfToken'    => Security::generateCsrfToken(),
        ]);
    }

    /**
     * Persists dish edits submitted via the edit form.
     *
     * Pipeline: CSRF check → field validation → optional image replacement (keeps
     * current_image when no new file is submitted) → DB update → allergen pivot
     * rebuild (delete then re-insert) → redirect to list.
     *
     * @param int $id Dish identifier.
     */
    public function update($id): void
    {
        Security::requireEmploye();

        if (!Security::verifyCsrfToken($_POST['csrf_token'])) {
            Session::setFlash(FlashMessage::INVALID_CSRF, 'error');
            $this->redirectToRoute(self::ROUTE_LIST);
            exit;
        }

        $_POST = Security::sanitizeInput($_POST);
        $fields = ['name', 'description', 'type'];
        $validate_required = Security::validateRequired($_POST, $fields);

        if ($validate_required !== []) {
            Session::setFlash(implode(', ', $validate_required), 'error');
            $this->redirectToRoute(self::ROUTE_CREATE);
            exit;
        }

        $imageUrl = $_POST['current_image'];

        if (!empty($_FILES['image']['name'])) {
            $file        = $_FILES['image'];
            $destination = dirname(__DIR__, 2) . '/public/uploads/dishes/';
            $allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
            $maxSize      = 5 * 1024 * 1024;

            if ($file['error'] !== UPLOAD_ERR_OK || $file['size'] > $maxSize) {
                Session::setFlash(FlashMessage::DISH_IMAGE_ERROR, 'error');
                $this->redirectToRoute(self::ROUTE_EDIT. $id);
                exit;
            }

            $imageInfo = getimagesize($file['tmp_name']);
            if ($imageInfo === false || !in_array($imageInfo['mime'], $allowedMimes, true)) {
                Session::setFlash(FlashMessage::DISH_IMAGE_ERROR, 'error');
                $this->redirectToRoute(self::ROUTE_EDIT. $id);
                exit;
            }

            $extensions = [
                'image/jpeg' => 'jpg',
                'image/png'  => 'png',
                'image/webp' => 'webp',
                'image/gif'  => 'gif',
            ];
            $filename = uniqid('dish_') . '.' . $extensions[$imageInfo['mime']];

            if (!move_uploaded_file($file['tmp_name'], $destination . $filename)) {
                Session::setFlash(FlashMessage::DISH_IMAGE_ERROR, 'error');
                $this->redirectToRoute(self::ROUTE_EDIT. $id);
                exit;
            }

            $imageUrl = '/uploads/dishes/' . $filename;
        }

        $dishId = $this->dishModel->update([
           'name'        => $_POST['name'],
           'description' => $_POST['description'],
           'url'         => $imageUrl,
           'id'          => $id
        ]);

        if (!$dishId) {
            Session::setFlash(FlashMessage::DISH_EDIT_ERROR, 'error');
            $this->redirectToRoute(self::ROUTE_EDIT. $id);
            exit;
        }

        $this->dishModel->deleteAllergenesByDishID($id);

        $allergenes = $_POST['allergenes'] ?? [];
        if (!empty($allergenes)) {
            $this->dishModel->addAllergenes($id, array_map('intval', $allergenes));
        }

        Session::setFlash(FlashMessage::DISH_UPDATED, 'success');
        $this->redirectToRoute(self::ROUTE_LIST);
        exit;
    }

    /**
     * Toggles a dish's active status via AJAX.
     *
     * Requires the CSRF token in the HTTP_X_CSRF_TOKEN header.
     * Returns a JSON response with a success or error key.
     *
     * @param int $id Dish identifier.
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

        if (!$this->dishModel->toggle($id)) {
            http_response_code(500);
            echo json_encode(['error' => FlashMessage::DISH_TOGGLE_ERROR]);
            exit;
        }

        echo json_encode(['success' => FlashMessage::DISH_TOGGLE_SUCCESS]);
        exit;
    }
}
