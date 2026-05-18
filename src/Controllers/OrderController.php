<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Security;
use App\Core\Session;
use App\Core\FlashMessage;
use App\Core\GeocodingService;
use App\Core\Mailer;
use App\Models\MenuModel;
use App\Models\OrderModel;

/**
 * Handles the order lifecycle: form display, submission, live price calculation (AJAX),
 * and order confirmation page.
 */
class OrderController extends AbstractController
{
    private const ORDER_ROUTE              = 'order/';
    private const ROUTE_MENU              = 'menu/';
    private const ROUTE_MENUS             = 'menus';
    private const ROUTE_PROFILE           = 'profile';
    private const ROUTE_ORDER_CONFIRMATION = 'order/confirmation/';
    private const ROUTE_ORDER_DETAIL      = 'order/detail/';
    private const ROUTE_ORDER_EDIT        = 'order/edit/';

    private MenuModel $menuModel;
    private OrderModel $orderModel;
    private GeocodingService $geocodingService;

    /** Initialises MenuModel, OrderModel, and GeocodingService dependencies. */
    public function __construct()
    {
        $this->menuModel = new MenuModel();
        $this->orderModel = new OrderModel();
        $this->geocodingService = new GeocodingService();
    }

    /**
     * Displays the order form for a given menu.
     *
     * Verifies the menu exists and has sufficient stock before rendering the view.
     * Redirects to the menu list or menu detail page on error.
     *
     * @param int $menuId Menu identifier.
     */
    public function create(int $menuId): void
    {
        $user = Security::requireAuth();

        $menu = $this->menuModel->findById($menuId);

        if (empty($menu)) {
            Session::setFlash(FlashMessage::WRONG_MENU, 'error');
            $this->redirectToRoute(self::ROUTE_MENUS);
            exit;
        }

        if (!isset($menu['stock']) || $menu['stock'] < $menu['min_people']) {
            Session::setFlash(FlashMessage::MENU_UNAVAILABLE, 'error');
            $this->redirectToRoute(self::ROUTE_MENU . $menuId);
            exit;
        }

        $this->renderView('orders/create.php', [
            'title'       => 'Commander : ' . $menu['title'],
            'description' => 'Passez votre commande',
            'menu'        => $menu,
            'user'        => $user,
            'csrfToken'   => Security::generateCsrfToken(),
            'scripts'     => ['/js/modules/OrderForm.js']
        ]);
    }

    /**
     * Persists a new order submitted via the order form.
     *
     * Pipeline: CSRF check → field validation → stock check → price computation
     * → transactional DB insert → confirmation e-mail → redirect to confirmation page.
     */
    public function store(): void
    {
        $user = Security::requireAuth();

        if (!isset($_POST['csrf_token']) || !Security::verifyCsrfToken($_POST['csrf_token'])) {
            Session::setFlash(FlashMessage::INVALID_CSRF, 'error');
            $this->redirectToRoute(self::ROUTE_MENUS);
            exit;
        }

        $input = $this->parseOrderPost();

        if ($input['menuId'] <= 0 || $input['nbPeople'] <= 0) {
            Session::setFlash(FlashMessage::GENERIC_ERROR, 'error');
            $this->redirectToRoute(self::ROUTE_MENUS);
            exit;
        }

        $error = $this->validateOrderFields($input);
        if ($error !== null) {
            Session::setFlash($error['message'], 'error');
            $this->redirectToRoute($error['route']);
            exit;
        }

        $menu = $this->menuModel->findById($input['menuId']);

        if (empty($menu)) {
            Session::setFlash(FlashMessage::WRONG_MENU, 'error');
            $this->redirectToRoute(self::ROUTE_MENUS);
            exit;
        }

        if ($input['nbPeople'] < $menu['min_people']) {
            Session::setFlash("Nombre minimum de personnes : {$menu['min_people']}", 'error');
            $this->redirectToRoute(self::ORDER_ROUTE . $input['menuId']);
            exit;
        }

        if (!$this->orderModel->checkStock($input['menuId'], $input['nbPeople'])) {
            Session::setFlash(FlashMessage::STOCK_INSUFFICIENT, 'error');
            $this->redirectToRoute(self::ROUTE_MENU . $input['menuId']);
            exit;
        }

        $pricing = $this->computeMenuPrice($menu, $input['nbPeople']);
        [$deliveryCost, , $geocodingError] = $this->computeDeliveryCost($input['deliveryAddress'], $input['deliveryCity']);

        if ($geocodingError !== null) {
            Session::setFlash($geocodingError, 'error');
            $this->redirectToRoute(self::ORDER_ROUTE . $input['menuId']);
            exit;
        }

        $orderData = [
            'user_id'          => $user['id'],
            'menu_id'          => $input['menuId'],
            'nb_people'        => $input['nbPeople'],
            'menu_price'       => round($pricing['menu_price'], 2),
            'delivery_cost'    => round($deliveryCost, 2),
            'total_price'      => round($pricing['menu_price_after_reduction'] + $deliveryCost, 2),
            'reduction'        => round($pricing['reduction'], 2),
            'delivery_address' => Security::sanitizeInput($input['deliveryAddress']),
            'delivery_city'    => Security::sanitizeInput($input['deliveryCity']),
            'delivery_date'    => $input['deliveryDate'],
            'delivery_time'    => $input['deliveryTime'],
            'status_id'        => 1
        ];

        $orderId = $this->orderModel->createWithTransaction($orderData, $user['id']);

        if (!$orderId) {
            Session::setFlash(FlashMessage::ORDER_ERROR, 'error');
            $this->redirectToRoute(self::ORDER_ROUTE . $input['menuId']);
            exit;
        }

        $this->sendOrderConfirmationEmail($orderId, $user, $menu, $orderData);

        Session::setFlash(FlashMessage::ORDER_SUCCESS, 'success');
        $this->redirectToRoute(self::ROUTE_ORDER_CONFIRMATION . $orderId);
    }

    /**
     * AJAX endpoint for live price estimation during order form completion.
     *
     * Requires an authenticated XMLHttpRequest with a CSRF token in the
     * HTTP_X_CSRF_TOKEN header. Returns a JSON object with menu price, discount,
     * delivery cost, computed distance, and total.
     */
    public function calculatePrice(): void
    {
        if (!Session::isAuthenticated()) {
            http_response_code(401);
            echo json_encode(['error' => 'Non autorisé']);
            exit;
        }

        $csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!Security::verifyCsrfToken($csrfToken)) {
            http_response_code(403);
            echo json_encode(['error' => FlashMessage::INVALID_CSRF]);
            exit;
        }

        $menuId          = (int) ($_POST['menu_id'] ?? 0);
        $nbPeople        = (int) ($_POST['nb_people'] ?? 0);
        $deliveryAddress = $_POST['delivery_address'] ?? '';
        $deliveryCity    = $_POST['delivery_city'] ?? '';

        $menu = $this->menuModel->findById($menuId);

        if (empty($menu)) {
            http_response_code(404);
            echo json_encode(['error' => 'Menu introuvable']);
            exit;
        }

        if ($nbPeople < $menu['min_people']) {
            echo json_encode(['error' => "Nombre minimum de personnes : {$menu['min_people']}"]);
            exit;
        }

        $pricing = $this->computeMenuPrice($menu, $nbPeople);
        [$deliveryCost, $distance, $geocodingError] = $this->computeDeliveryCost($deliveryAddress, $deliveryCity);

        if ($geocodingError !== null) {
            echo json_encode(['error' => $geocodingError]);
            exit;
        }

        header('Content-Type: application/json');
        echo json_encode([
            'menu_price'                 => round($pricing['menu_price'], 2),
            'reduction'                  => round($pricing['reduction'], 2),
            'menu_price_after_reduction' => round($pricing['menu_price_after_reduction'], 2),
            'delivery_cost'              => round($deliveryCost, 2),
            'distance'                   => $distance,
            'total_price'                => round($pricing['menu_price_after_reduction'] + $deliveryCost, 2)
        ]);
        exit;
    }

    /**
     * Displays the order confirmation page.
     *
     * Ensures the order belongs to the authenticated user before rendering.
     * Redirects to the profile page on error or unauthorized access.
     *
     * @param int $orderId Order identifier.
     */
    public function confirmation(int $orderId): void
    {
        $user = Security::requireAuth();

        $order = $this->orderModel->findById($orderId);

        if (empty($order)) {
            Session::setFlash(FlashMessage::ORDER_NOT_FOUND, 'error');
            $this->redirectToRoute(self::ROUTE_PROFILE);
            exit;
        }

        if ($order['user_id'] !== $user['id']) {
            Session::setFlash(FlashMessage::ACCESS_DENIED, 'error');
            $this->redirectToRoute(self::ROUTE_PROFILE);
            exit;
        }

        $this->renderView('orders/confirmation.php', [
            'title'       => 'Confirmation de commande',
            'description' => 'Votre commande a été enregistrée',
            'order'       => $order
        ]);
    }

    /**
     * Displays the detail page for a single order, including its status history.
     *
     * Ensures the order belongs to the authenticated user before rendering.
     * Redirects to the profile page on error or unauthorized access.
     *
     * @param int $orderId Order identifier.
     */
    public function show(int $orderId): void
    {
        $user = Security::requireAuth();

        $order = $this->orderModel->findById($orderId);
        $statuses = $this->orderModel->getAllStatus();

        if (empty($order)) {
            Session::setFlash(FlashMessage::ORDER_NOT_FOUND, 'error');
            $this->redirectToRoute(self::ROUTE_PROFILE);
            exit;
        }

        if ($order['user_id'] !== $user['id'] && !in_array($user['role'], ['employe', 'admin'])) {
            Session::setFlash(FlashMessage::ACCESS_DENIED, 'error');
            $this->redirectToRoute(self::ROUTE_PROFILE);
            exit;
        }

        $history = $this->orderModel->getStatusHistory($orderId);

        $this->renderView('orders/show.php', [
            'user'        => $user,
            'title'       => 'Commande '. $orderId,
            'description' => 'Détail de la commande '. $orderId,
            'csrfToken'   => Security::generateCsrfToken(),
            'order'       => $order,
            'history'     => $history,
            'statuses'    => $statuses
        ]);
    }

    /**
     * Handles order editing (GET renders the form, POST processes the update).
     *
     * GET: verifies the order exists and belongs to the authenticated user, then renders the form.
     * POST: CSRF check → ownership check (owner or employe/admin) → field validation
     * → delivery cost computation → price recomputation → model update → redirect to detail.
     *
     * @param int $orderId Order to edit.
     */
    public function edit(int $orderId): void
    {
        $user = Security::requireAuth();
        $order = $this->orderModel->findById($orderId);
        $menu = $this->menuModel->findById($order['menu_id']);

        if ($_SERVER['REQUEST_METHOD'] === 'GET') {

            if (empty($order)) {
                Session::setFlash(FlashMessage::ORDER_NOT_FOUND, 'error');
                $this->redirectToRoute(self::ROUTE_PROFILE);
                exit;
            }
            if ($order['user_id'] !== $user['id']) {
                Session::setFlash(FlashMessage::ACCESS_DENIED, 'error');
                $this->redirectToRoute(self::ROUTE_PROFILE);
                exit;
            }

            $this->renderView('orders/edit.php', [
                'user'      => $user,
                'csrfToken' => Security::generateCsrfToken(),
                'title'     => 'Modifier ma commande '. $orderId,
                'order'     => $order,
                'menu'      => $menu,
                'scripts' => ['/js/modules/OrderForm.js']
            ]);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            if (!Security::verifyCsrfToken($_POST['csrf_token'])) {
                Session::setFlash(FlashMessage::INVALID_CSRF, 'error');
                $this->redirectToRoute(self::ROUTE_PROFILE);
                exit;
            }

            if ($order['user_id'] !== $user['id'] && !in_array($user['role'], ['employe', 'admin'])) {
                Session::setFlash(FlashMessage::ACCESS_DENIED, 'error');
                $this->redirectToRoute(self::ROUTE_PROFILE);
                exit;
            }

            $_POST = Security::sanitizeInput($_POST);

            $fields = ['nb_people','delivery_address','delivery_city','delivery_date','delivery_time'];
            $validate_required = Security::validateRequired($_POST, $fields);
            if ($validate_required !== []) {
                Session::setFlash(implode(', ', $validate_required), 'error');
                $this->redirectToRoute(self::ROUTE_PROFILE);
                exit;
            }

            $delivery = $this->computeDeliveryCost($_POST['delivery_address'], $_POST['delivery_city']);
            if (!empty($delivery[2])) {
                Session::setFlash(FlashMessage::GEOCODING_ERROR, 'error');
                $this->redirectToRoute(self::ROUTE_ORDER_EDIT . $orderId);
                exit;
            }

            $price = $this->computeMenuPrice($menu, (int) $_POST['nb_people']);
            $data = [
                'nb_people'        => $_POST['nb_people'],
                'delivery_address' => $_POST['delivery_address'],
                'delivery_city'    => $_POST['delivery_city'],
                'delivery_date'    => $_POST['delivery_date'],
                'delivery_time'    => $_POST['delivery_time'],
                'menu_price'       => $price['menu_price'],
                'delivery_cost'    => $delivery[0],
                'total_price'      => $price['menu_price_after_reduction'] + $delivery[0],
                'reduction'        => $price['reduction']
            ];

            if (!$this->orderModel->update($orderId, $data)) {
                Session::setFlash(FlashMessage::UPDATE_ERROR, 'error');
                $this->redirectToRoute(self::ROUTE_PROFILE);
                exit;
            }

            Session::setFlash(FlashMessage::ORDER_UPDATED, 'success');
            $this->redirectToRoute(self::ROUTE_ORDER_DETAIL . $orderId);
        }
    }

    /**
     * Cancels an order on behalf of the authenticated user.
     *
     * Pipeline: CSRF check → order existence check → ownership check
     * → model cancellation (only allowed while status = 1) → redirect to profile.
     *
     * @param int $orderId Order to cancel.
     */
    public function cancel(int $orderId): void
    {
        $user = Security::requireAuth();

        if (!isset($_POST['csrf_token']) || !Security::verifyCsrfToken($_POST['csrf_token'])) {
            Session::setFlash(FlashMessage::INVALID_CSRF, 'error');
            $this->redirectToRoute(self::ROUTE_PROFILE);
            exit;
        }

        $order = $this->orderModel->findById($orderId);

        if (empty($order)) {
            Session::setFlash(FlashMessage::ORDER_NOT_FOUND, 'error');
            $this->redirectToRoute(self::ROUTE_PROFILE);
            exit;
        }

        if ($user['id'] !== $order['user_id']) {
            Session::setFlash(FlashMessage::ACCESS_DENIED, 'error');
            $this->redirectToRoute(self::ROUTE_PROFILE);
            exit;
        }

        if ($this->orderModel->cancel($orderId, $user['id'])) {
            Session::setFlash(FlashMessage::CANCEL_ORDER, 'success');
            $this->redirectToRoute(self::ROUTE_PROFILE);
            exit;
        } else {
            Session::setFlash(FlashMessage::CANCEL_ORDER_ERROR, 'error');
            $this->redirectToRoute(self::ROUTE_PROFILE);
            exit;
        }
    }

    /**
     * Extracts and normalises order fields from $_POST.
     *
     * @return array{
     *     menuId: int,
     *     nbPeople: int,
     *     deliveryAddress: string,
     *     deliveryCity: string,
     *     deliveryDate: string,
     *     deliveryTime: string
     * }
     */
    private function parseOrderPost(): array
    {
        return [
            'menuId'          => (int) ($_POST['menu_id'] ?? 0),
            'nbPeople'        => (int) ($_POST['nb_people'] ?? 0),
            'deliveryAddress' => trim($_POST['delivery_address'] ?? ''),
            'deliveryCity'    => trim($_POST['delivery_city'] ?? ''),
            'deliveryDate'    => trim($_POST['delivery_date'] ?? ''),
            'deliveryTime'    => trim($_POST['delivery_time'] ?? ''),
        ];
    }

    /**
     * Validates the delivery fields from the order form.
     *
     * Checks required fields, date format (Y-m-d, at least tomorrow),
     * and time format (H:i).
     *
     * @param array $input Data from parseOrderPost().
     * @return array{message: string, route: string}|null Validation error, or null if valid.
     */
    private function validateOrderFields(array $input): ?array
    {
        $route  = self::ORDER_ROUTE . $input['menuId'];
        $fields = [
            'delivery_address' => $input['deliveryAddress'],
            'delivery_city'    => $input['deliveryCity'],
            'delivery_date'    => $input['deliveryDate'],
            'delivery_time'    => $input['deliveryTime'],
        ];

        $missingFields = Security::validateRequired($fields, array_keys($fields));
        if (!empty($missingFields)) {
            return ['message' => implode(', ', $missingFields), 'route' => $route];
        }

        $checks = [
            FlashMessage::WRONG_DATE => !$this->isValidDeliveryDate($input['deliveryDate']),
            FlashMessage::WRONG_TIME => !$this->isValidDeliveryTime($input['deliveryTime']),
        ];

        foreach ($checks as $message => $invalid) {
            if ($invalid) {
                return ['message' => $message, 'route' => $route];
            }
        }

        return null;
    }

    /**
     * Computes the menu price, optional discount, and price after discount.
     *
     * The unit price is derived from base_price / min_people. A 10% discount
     * is applied when nb_people >= min_people + 5.
     *
     * @param array $menu     Menu data (base_price, min_people).
     * @param int   $nbPeople Number of guests.
     * @return array{menu_price: float, reduction: float, menu_price_after_reduction: float}
     */
    private function computeMenuPrice(array $menu, int $nbPeople): array
    {
        $menuPrice = $menu['base_price'] * $nbPeople;
        $reduction = $nbPeople >= ($menu['min_people'] + 5) ? $menuPrice * 0.10 : 0.0;
        return [
            'menu_price'                 => $menuPrice,
            'reduction'                  => $reduction,
            'menu_price_after_reduction' => $menuPrice - $reduction,
        ];
    }

    /**
     * Computes the delivery cost from the destination address.
     *
     * Bordeaux deliveries are free. For all other cities the distance is fetched
     * via GeocodingService and the rate is: €5 + €0.59/km.
     * Returns [0, null, null] when the address or city is empty (partial input).
     *
     * @param string $address Delivery address.
     * @param string $city    Delivery city.
     * @return array{0: float, 1: float|null, 2: string|null} [cost, distance in km, error message]
     */
    private function computeDeliveryCost(string $address, string $city): array
    {
        if ($city === 'Bordeaux' || empty($address) || empty($city)) {
            return [0, null, null];
        }

        $cost     = 0;
        $distance = null;
        $error    = null;

        try {
            $distance = $this->geocodingService->getDistanceFromBordeaux($address, $city);
            if ($distance !== null) {
                $cost = 5 + ($distance * 0.59);
            } else {
                $error = FlashMessage::GEOCODING_ERROR;
            }
        } catch (\Exception) {
            $error = FlashMessage::GEOCODING_DISTANCE_ERROR;
        }

        return [$cost, $distance, $error];
    }

    /**
     * Validates a date string against format Y-m-d and ensures it is at least tomorrow.
     *
     * @param string $date Date to validate.
     */
    private function isValidDeliveryDate(string $date): bool
    {
        $dateObject = \DateTime::createFromFormat('Y-m-d', $date);
        if (!$dateObject || $dateObject->format('Y-m-d') !== $date) {
            return false;
        }
        $tomorrow = (new \DateTime())->setTime(0, 0)->modify('+1 day');
        return $dateObject >= $tomorrow;
    }

    /**
     * Validates a time string against format H:i (e.g. 14:30).
     *
     * @param string $time Time to validate.
     */
    private function isValidDeliveryTime(string $time): bool
    {
        $timeObject = \DateTime::createFromFormat('H:i', $time);
        return $timeObject && $timeObject->format('H:i') === $time;
    }

    /**
     * Sends the order confirmation e-mail to the user.
     *
     * Send failures are silently caught and logged via error_log so they never
     * block the order confirmation flow.
     *
     * @param int   $orderId   ID of the created order.
     * @param array $user      User data (email, nom, prenom).
     * @param array $menu      Menu data (title).
     * @param array $orderData Order data as stored in the database.
     */
    private function sendOrderConfirmationEmail(int $orderId, array $user, array $menu, array $orderData): void
    {
        try {
            $mailer = new Mailer();

            $mailer->sendWithTemplate(
                $user['email'],
                $user['nom'],
                'Confirmation de votre commande',
                'order_confirmation',
                [
                    'nom'              => $user['nom'],
                    'prenom'           => $user['prenom'],
                    'order_id'         => $orderId,
                    'menu_title'       => $menu['title'],
                    'nb_people'        => $orderData['nb_people'],
                    'delivery_date'    => date('d/m/Y', strtotime($orderData['delivery_date'])),
                    'delivery_time'    => $orderData['delivery_time'],
                    'delivery_address' => $orderData['delivery_address'],
                    'delivery_city'    => $orderData['delivery_city'],
                    'menu_price'       => number_format($orderData['menu_price'], 2, ',', ' '),
                    'reduction'        => number_format($orderData['reduction'], 2, ',', ' '),
                    'delivery_cost'    => number_format($orderData['delivery_cost'], 2, ',', ' '),
                    'total_price'      => number_format($orderData['total_price'], 2, ',', ' ')
                ]
            );
        } catch (\Exception $e) {
            error_log('Erreur envoi email confirmation commande : ' . $e->getMessage());
        }
    }
}
