<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Security;
use App\Core\Session;
use App\Core\FlashMessage;
use App\Models\ReviewModel;
use App\Models\OrderModel;
use App\Core\Mailer;

class ReviewController extends AbstractController
{
    private ReviewModel $reviewModel;
    private OrderModel $orderModel;

    private const ROUTE_ORDER = 'order/detail/';
    private const ROUTE_CREATE = 'review/';
    private const ROUTE_LIST = 'employe/reviews';
    private const STATUS_TERMINEE = 7;

    /** Initialises ReviewModel and OrderModel dependencies. */
    public function __construct()
    {
        $this->reviewModel = new ReviewModel();
        $this->orderModel = new OrderModel();
    }

    /**
     * Renders the review creation form for a completed order.
     *
     * Requires authentication. Runs guards to verify order ownership and
     * that no review has already been submitted for this order.
     */
    public function create(int $id): void
    {
        $user = Security::requireAuth();
        $order = $this->orderModel->findById($id);

        $this->guards($id, $order, $user['id'], self::ROUTE_ORDER);

        $this->renderView('review/create.php', [
            'csrfToken'   => Security::generateCsrfToken(),
            'title'       => 'Donnez votre Avis',
            'description' => "Page de rédaction d'avis",
            'user'        => $user,
            'order'       => $order
        ]);
    }

    /**
     * Validates and persists a new review submitted from the creation form.
     *
     * Pipeline: CSRF check → authentication → required fields → rating range (1–5)
     * → ownership/status guards → model insert → redirect to order detail.
     */
    public function store(int $id): void
    {
        if (!isset($_POST['csrf_token']) || !Security::verifyCsrfToken($_POST['csrf_token'])) {
            Session::setFlash(FlashMessage::INVALID_CSRF, 'error');
            $this->redirectToRoute(self::ROUTE_CREATE. $id);
            exit;
        }

        $user = Security::requireAuth();
        $order = $this->orderModel->findById($id);
        $_POST = Security::sanitizeInput($_POST);

        $fields = ['rating','comment'];
        $validate_required = Security::validateRequired($_POST, $fields);
        if ($validate_required !== []) {
            Session::setFlash(implode(', ', $validate_required), 'error');
            $this->redirectToRoute(self::ROUTE_CREATE. $id);
            exit;
        }
        $rating = (int) $_POST['rating'];
        if ($rating < 1 || $rating > 5) {
            Session::setFlash(FlashMessage::RATING_ERROR, 'error');
            $this->redirectToRoute(self::ROUTE_CREATE. $id);
            exit;
        }

        $this->guards($id, $order, $user['id'], self::ROUTE_CREATE);

        $this->reviewModel->create([
            'order_id' => $order['id'],
            'user_id' => $user['id'],
            'rating' => $_POST['rating'],
            'comment' => $_POST['comment'],
        ]);
        Session::setFlash(FlashMessage::REVIEW_SUCCESS, 'success');
        $this->redirectToRoute(self::ROUTE_ORDER. $id);
        exit;
    }

    /**
     * Renders the employee moderation page listing all pending reviews.
     *
     * Requires employee role.
     */
    public function list(): void
    {
        $user = Security::requireEmploye();
        $reviews = $this->reviewModel->findPending();

        $this->renderView('employe/reviews.php', [
            'csrfToken'   => Security::generateCsrfToken(),
            'title'       => 'Liste des avis',
            'description' => "Page de modération des avis avant publication",
            'user'        => $user,
            'reviews'     => $reviews,
            'scripts'     => ['/js/modules/ReviewReject.js']
        ]);
    }

    /**
     * Approves or rejects a pending review and notifies the author by e-mail.
     *
     * Pipeline: CSRF check → employee auth → review existence check
     * → status validation → rejection reason requirement → model update → email → redirect.
     */
    public function valid(int $id): void
    {
        if (!isset($_POST['csrf_token']) || !Security::verifyCsrfToken($_POST['csrf_token'])) {
            Session::setFlash(FlashMessage::INVALID_CSRF, 'error');
            $this->redirectToRoute(self::ROUTE_LIST);
            exit;
        }

        $user = Security::requireEmploye();
        $review = $this->reviewModel->findByReviewId($id);
        $_POST = Security::sanitizeInput($_POST);

        if ($review === null) {
            Session::setFlash(FlashMessage::WRONG_REVIEW, 'error');
            $this->redirectToRoute(self::ROUTE_LIST);
            exit;
        }

        if (empty($_POST['status']) || !in_array($_POST['status'], ['approved', 'rejected'])) {
            Session::setFlash(FlashMessage::STATUS_ERROR, 'error');
            $this->redirectToRoute(self::ROUTE_LIST);
            exit;
        }

        if ($_POST['status'] === 'rejected' && empty($_POST['comment'])) {
            Session::setFlash(FlashMessage::COMMENT_ERROR, 'error');
            $this->redirectToRoute(self::ROUTE_LIST);
            exit;
        }

        $this->reviewModel->validate($id, $_POST['status'], $user['id'], $_POST['comment'] ?? '');
        $order = $this->orderModel->findById($review['order_id']);

        $this->sendValidReviewMail($_POST['status'], $order, $_POST['comment'] ?? '');

        Session::setFlash(FlashMessage::VALID_SUCCESS, 'success');
        $this->redirectToRoute(self::ROUTE_LIST);
        exit;
    }

    /**
     * Aborts with a flash and redirect if any access condition is not met.
     *
     * Checks: order existence, ownership by the authenticated user,
     * order status is "completed" (STATUS_TERMINEE), and no review already submitted.
     */
    private function guards(int $id, ?array $order, string $userId, string $route): void
    {
        if (!$order) {
            Session::setFlash(FlashMessage::ORDER_NOT_FOUND, 'error');
            $this->redirectToRoute($route . $id);
            exit;
        }

        if ($userId !== $order['user_id']) {
            Session::setFlash(FlashMessage::ACCESS_DENIED, 'error');
            $this->redirectToRoute($route . $id);
            exit;
        }

        if ($order['status_id'] !== self::STATUS_TERMINEE) {
            Session::setFlash(FlashMessage::ACCESS_DENIED, 'error');
            $this->redirectToRoute($route . $id);
            exit;
        }

        if ($this->reviewModel->findByOrderId($id) !== null) {
            Session::setFlash(FlashMessage::REVIEW_EXIST, 'error');
            $this->redirectToRoute($route . $id);
            exit;
        }
    }

    /**
     * Sends a review validation notification e-mail to the customer.
     *
     * Includes approval/rejection status and, when rejected, the moderator's reason.
     * Failures are silently caught and logged so they never block the moderation flow.
     *
     * @param string $status    'approved' or 'rejected'.
     * @param array  $orderData Order row including customer name, e-mail, and menu title.
     * @param string $comment   Rejection reason (empty string when approved).
     */
    private function sendValidReviewMail(string $status, array $orderData, string $comment = ''): void
    {
        try {
            $mailer = new Mailer();

            $mailer->sendWithTemplate(
                $orderData['email'],
                $orderData['nom'],
                'Validation de votre avis',
                'valid-review',
                [
                    'nom'              => $orderData['nom'],
                    'prenom'           => $orderData['prenom'],
                    'order_id'         => $orderData['id'],
                    'menu_title'       => $orderData['menu_title'],
                    'delivery_date'    => date('d/m/Y', strtotime($orderData['delivery_date'])),
                    'delivery_time'    => $orderData['delivery_time'],
                    'status'           => $status === 'approved' ? 'Avis publié' : 'Avis refusé',
                    'comment_label'    => $status === 'rejected' ? 'Raison du refus :' : '',
                    'comment'          => $comment
                ]
            );
        } catch (\Exception $e) {
            error_log('Erreur envoi email modification statut de commande : ' . $e->getMessage());
        }
    }
}
