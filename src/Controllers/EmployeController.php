<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\FlashMessage;
use App\Core\Security;
use App\Core\Session;
use App\Core\Mailer;
use App\Models\OrderModel;

/**
 * Handles employee-specific actions: order management and status updates.
 */
class EmployeController extends AbstractController
{
    private OrderModel $orderModel;
    private const ORDER = 'order/detail/';

    public function __construct()
    {
        $this->orderModel = new OrderModel();
    }

    /**
     * Displays the order management list for employees.
     *
     * Accepts optional GET filters: status_id (int) and search (string, sanitized).
     * Passes all available statuses for the filter dropdown and the filtered order list.
     */
    public function orders(): void
    {
        $user = Security::requireEmploye();
        $status = $this->orderModel->getAllStatus();
        $filters = [];

        if (!empty($_GET['status_id'])) {
            $filters['status_id'] = (int) $_GET['status_id'];
        }
        if (!empty($_GET['search'])) {
            $filters['search'] = Security::sanitizeInput($_GET['search']);
        }

        $this->renderView('employe/orders.php', [
                'user' => $user,
                'csrfToken' => Security::generateCsrfToken(),
                'title' => 'Affichage des commandes',
                'statuses' => $status,
                'orders' => $this->orderModel->findAllFiltered($filters)
            ]);
    }

    /**
     * Updates the status of an order and notifies the customer by email.
     *
     * Pipeline: CSRF check → status_id validation → model update
     * → confirmation email → redirect to order detail.
     *
     * @param int $orderId Order whose status to update.
     */
    public function updateStatus(int $orderId): void
    {
        $user = Security::requireEmploye();

        if (!Security::verifyCsrfToken($_POST['csrf_token'])) {
            Session::setFlash(FlashMessage::INVALID_CSRF, 'error');
            $this->redirectToRoute(self::ORDER . $orderId);
            exit;
        }

        $statusId = (int) $_POST['status_id'];
        $comment = Security::sanitizeInput($_POST['comment']);

        if ($statusId === 0) {
            Session::setFlash(FlashMessage::GENERIC_ERROR, 'error');
            $this->redirectToRoute(self::ORDER . $orderId);
            exit;
        }

        if (!$this->orderModel->updateStatus($orderId, $statusId, $user['id'], $comment)) {
            Session::setFlash(FlashMessage::GENERIC_ERROR, 'error');
            $this->redirectToRoute(self::ORDER . $orderId);
            exit;
        }

        $orderData = $this->orderModel->findById($orderId);

        $this->sendStatusUpdateOrderEmail($orderId, $orderData, $comment);
        Session::setFlash(FlashMessage::STATUS_UPDATED, 'success');
        $this->redirectToRoute(self::ORDER . $orderId);
        exit;
    }

    /**
     * Cancels an order on behalf of an employee, with a mandatory reason and contact method.
     *
     * Pipeline: CSRF check → field validation (cancellation_reason, contact_method)
     * → contact_method whitelist check → model cancellation → status email → redirect.
     *
     * @param int $orderId Order to cancel.
     */
    public function cancelOrder(int $orderId): void
    {
        $user = Security::requireEmploye();

        if (!Security::verifyCsrfToken($_POST['csrf_token'])) {
            Session::setFlash(FlashMessage::INVALID_CSRF, 'error');
            $this->redirectToRoute(self::ORDER . $orderId);
            exit;
        }

        $_POST = Security::sanitizeInput($_POST);

        $fields = ['cancellation_reason','contact_method'];
        $validate_required = Security::validateRequired($_POST, $fields);
        if ($validate_required !== []) {
            Session::setFlash(implode(', ', $validate_required), 'error');
            $this->redirectToRoute(self::ORDER . $orderId);
            exit;
        }

        if (!in_array($_POST['contact_method'], ['email', 'telephone'])) {
            Session::setFlash(FlashMessage::CANCEL_ORDER_ERROR, 'error');
            $this->redirectToRoute(self::ORDER . $orderId);
            exit;
        }

        if (!$this->orderModel->cancelByEmploye($orderId, $user, $_POST)) {
            Session::setFlash(FlashMessage::CANCEL_ORDER_ERROR, 'error');
            $this->redirectToRoute(self::ORDER . $orderId);
            exit;
        }

        $orderData = $this->orderModel->findById($orderId);

        $this->sendStatusUpdateOrderEmail($orderId, $orderData, $_POST['cancellation_reason']);
        Session::setFlash(FlashMessage::CANCEL_ORDER, 'success');
        $this->redirectToRoute(self::ORDER . $orderId);
        exit;
    }

    /**
     * Sends a status-update notification email to the customer.
     *
     * Send failures are silently caught and logged so they never block the update flow.
     *
     * @param int    $orderId   Order identifier, included in the email body.
     * @param array  $orderData Order row with email, nom, prenom, menu_title, delivery_date,
     *                          delivery_time, status_name.
     * @param string $comment   Optional employee comment appended to the email.
     */
    private function sendStatusUpdateOrderEmail(int $orderId, array $orderData, string $comment): void
    {
        try {
            $mailer = new Mailer();

            $mailer->sendWithTemplate(
                $orderData['email'],
                $orderData['nom'],
                'Changement de statut de votre commande',
                'order-status-update',
                [
                    'nom'              => $orderData['nom'],
                    'prenom'           => $orderData['prenom'],
                    'order_id'         => $orderId,
                    'menu_title'       => $orderData['menu_title'],
                    'delivery_date'    => date('d/m/Y', strtotime($orderData['delivery_date'])),
                    'delivery_time'    => $orderData['delivery_time'],
                    'status_name'      => $orderData['status_name'],
                    'comment_label' => !empty($comment) ? 'Commentaire :' : '',
                    'comment'       => $comment
                ]
            );
        } catch (\Exception $e) {
            error_log('Erreur envoi email modification statut de commande : ' . $e->getMessage());
        }
    }
}
