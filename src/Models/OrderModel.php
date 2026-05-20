<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\DatabaseConnection;
use PDO;
use PDOException;

/**
 * Data-access layer for orders, order status history, and menu stock management.
 */
class OrderModel
{
    /**
     * Returns the shared PDO instance.
     */
    private static function getDb(): PDO
    {
        return DatabaseConnection::getInstance();
    }

    /**
     * Inserts a new order row and returns its auto-incremented ID.
     *
     * Low-level insert — prefer createWithTransaction() for normal order placement
     * as it also records status history and decrements stock atomically.
     *
     * @param array $orderData Validated order fields (user_id, menu_id, nb_people,
     *                         menu_price, delivery_cost, total_price, reduction,
     *                         delivery_address, delivery_city, delivery_date,
     *                         delivery_time, status_id).
     * @return int|null New order ID, or null if the insert failed.
     */
    public function create(array $orderData): ?int
    {
        $query = "
            INSERT INTO orders (
                user_id, menu_id, nb_people, menu_price, delivery_cost,
                total_price, reduction, delivery_address, delivery_city,
                delivery_date, delivery_time, status_id
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ";

        $stmt = self::getDb()->prepare($query);
        $success = $stmt->execute([
            $orderData['user_id'],
            $orderData['menu_id'],
            $orderData['nb_people'],
            $orderData['menu_price'],
            $orderData['delivery_cost'],
            $orderData['total_price'],
            $orderData['reduction'],
            $orderData['delivery_address'],
            $orderData['delivery_city'],
            $orderData['delivery_date'],
            $orderData['delivery_time'],
            $orderData['status_id']
        ]);

        return $success ? (int) self::getDb()->lastInsertId() : null;
    }

    /**
     * Creates an order atomically: inserts the row, records the initial status history,
     * and decrements the menu stock — all within a single database transaction.
     *
     * Rolls back and logs the error if any step fails.
     *
     * @param array $orderData Validated order fields (see create()).
     * @param string $userId    UUID of the user placing the order, stored in the status history.
     * @return int|null New order ID, or null if the transaction was rolled back.
     */
    public function createWithTransaction(array $orderData, string $userId): ?int
    {
        try {
            self::getDb()->beginTransaction();

            $orderId = $this->create($orderData);

            if (!$orderId) {
                throw new PDOException('Order creation failed');
            }

            $this->addStatusHistory($orderId, 1, $userId, '');

            if (!$this->decrementStock($orderData['menu_id'], $orderData['nb_people'])) {
                throw new PDOException('Stock update failed');
            }

            self::getDb()->commit();

            return $orderId;

        } catch (\Exception $e) {
            self::getDb()->rollBack();
            error_log('Order creation error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Cancels an order atomically, provided it is still in the initial status (id = 1).
     *
     * Updates the order status to cancelled (id = 8) and appends a history entry —
     * both within a single transaction. Returns false if the order has already been
     * processed (rowCount = 0) or if any step throws.
     *
     * @param int    $orderId Order to cancel.
     * @param string $userId  User requesting the cancellation, stored in the history entry.
     * @return bool True if the order was successfully cancelled.
     */
    public function cancel(int $orderId, string $userId): bool
    {
        try {
            self::getDb()->beginTransaction();

            $query = "
            UPDATE orders SET status_id = 8
            WHERE id = ?
            AND status_id = 1
            ";

            $stmt = self::getDb()->prepare($query);
            $stmt->execute([$orderId]);
            if ($stmt->rowCount() !== 1) {
                throw new PDOException('Cannot cancel the order: it has already been processed.');
            }

            $this->addStatusHistory($orderId, 8, $userId, 'Order cancelled at customer request.');

            self::getDb()->commit();

            return true;
        } catch (\Exception $e) {
            self::getDb()->rollBack();
            error_log('Order cancellation error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Finds an order by primary key, joining user, menu, and status data.
     *
     * @param int $id Order identifier.
     * @return array|null Order row with nom, prenom, email, menu_title, status_name,
     *                    or null if not found.
     */
    public function findById(int $id): ?array
    {
        $query = "
            SELECT o.*, u.nom, u.prenom, u.email, m.title AS menu_title, m.description AS menu_description,
                   s.name AS status_name
            FROM orders AS o
            JOIN users AS u ON o.user_id = u.id
            JOIN menus AS m ON o.menu_id = m.id
            JOIN order_status AS s ON o.status_id = s.id
            WHERE o.id = ?
        ";

        $stmt = self::getDb()->prepare($query);
        $stmt->execute([$id]);

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Returns all orders for a given user, most recent first.
     *
     * @param string $userId User identifier.
     * @return array List of order rows with menu_title and status_name, ordered by created_at DESC.
     */
    public function findByUserId(string $userId): array
    {
        $query = "
            SELECT o.*, m.title AS menu_title, s.name AS status_name
            FROM orders AS o
            JOIN menus AS m ON o.menu_id = m.id
            JOIN order_status AS s ON o.status_id = s.id
            WHERE o.user_id = ?
            ORDER BY o.created_at DESC
        ";

        $stmt = self::getDb()->prepare($query);
        $stmt->execute([$userId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Returns all orders matching the given filters, most recent first.
     *
     * Supported filters:
     * - status_id (int)  : restrict to a specific order status.
     * - search   (string): LIKE match against user nom, prenom, or email.
     *
     * Filters are combined with AND. Omitting a key disables that filter.
     *
     * @param array $filters Associative array of optional filter criteria.
     * @return array Order rows with nom, prenom, email, menu_title, status_name.
     */
    public function findAllFiltered(array $filters = []): array
    {
        $query = "
            SELECT o.*, u.nom, u.prenom, u.email, u.gsm, m.title AS menu_title, s.name AS status_name
            FROM orders AS o
            JOIN users AS u ON o.user_id = u.id
            JOIN menus AS m ON o.menu_id = m.id
            JOIN order_status AS s ON o.status_id = s.id
        ";

        $conditions = [];
        $params = [];

        if (isset($filters['status_id'])) {
            $conditions[] = 'o.status_id = ?';
            $params[] = $filters['status_id'];
        }

        if (isset($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $conditions[] = '(u.nom LIKE ? OR u.prenom LIKE ? OR u.email LIKE ?)';
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
        }

        if (!empty($conditions)) {
            $query .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $query .= ' ORDER BY o.created_at DESC';

        $stmt = self::getDb()->prepare($query);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Appends an entry to the order status history.
     *
     * @param int         $orderId  Order whose status changed.
     * @param int         $statusId New status identifier.
     * @param int         $userId   User who triggered the change.
     * @param string|null $comment  Optional free-text note attached to the history entry.
     * @return bool True if the row was inserted successfully.
     */
    public function addStatusHistory(int $orderId, int $statusId, string $userId, ?string $comment = null): bool
    {
        $query = "
            INSERT INTO order_status_history (order_id, status_id, changed_by, comment)
            VALUES (?, ?, ?, ?)
        ";

        $stmt = self::getDb()->prepare($query);
        return $stmt->execute([$orderId, $statusId, $userId, $comment]);
    }

    /**
     * Decrements the stock of a menu by the given quantity.
     *
     * The UPDATE includes a `stock >= quantity` guard: if stock is insufficient
     * the WHERE clause matches no rows, rowCount() returns 0, and this method
     * returns false — preventing negative stock and signalling failure to the caller.
     *
     * @param int $menuId   Menu whose stock to decrement.
     * @param int $quantity Number of units to subtract.
     * @return bool True if the stock was updated (i.e. sufficient stock existed).
     */
    public function decrementStock(int $menuId, int $quantity): bool
    {
        $query = "
            UPDATE menus
            SET stock = stock - ?
            WHERE id = ? AND stock >= ?
        ";

        $stmt = self::getDb()->prepare($query);
        $stmt->execute([$quantity, $menuId, $quantity]);
        return $stmt->rowCount() === 1;
    }

    /**
     * Returns true if the menu has at least the requested quantity in stock.
     *
     * @param int $menuId   Menu to check.
     * @param int $quantity Minimum required stock.
     */
    public function checkStock(int $menuId, int $quantity): bool
    {
        $query = "SELECT stock FROM menus WHERE id = ?";
        $stmt = self::getDb()->prepare($query);
        $stmt->execute([$menuId]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result && $result['stock'] >= $quantity;
    }

    /**
     * Returns the full status history for an order, oldest entry first.
     *
     * Each row includes the human-readable status label (from order_status)
     * and the nom/prenom of the user who triggered the change (from users).
     *
     * @param int $orderId Order identifier.
     * @return array List of history rows with status_name, changed_at, comment,
     *               changed_by_nom, changed_by_prenom.
     */
    public function getStatusHistory(int $orderId): array
    {
        $query = "
            SELECT h.changed_at, h.comment,
                   s.name AS status_name,
                   u.nom AS changed_by_nom, u.prenom AS changed_by_prenom
            FROM order_status_history AS h
            JOIN order_status AS s ON h.status_id = s.id
            JOIN users AS u ON h.changed_by = u.id
            WHERE h.order_id = ?
            ORDER BY h.changed_at ASC
        ";

        $stmt = self::getDb()->prepare($query);
        $stmt->execute([$orderId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Returns all order statuses sorted by workflow order.
     *
     * @return array Rows with id and name, ordered by workfow_order ASC.
     */
    public function getAllStatus(): array
    {
        $query = "
            SELECT id, name FROM order_status
            ORDER BY workflow_order ASC
        ";
        $stmt = self::getDb()->prepare($query);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Updates the status of an order atomically and records the change in the history.
     *
     * Rolls back if the UPDATE matches no row or if the history insert fails.
     *
     * @param int    $orderId  Order to update.
     * @param int    $statusId New status identifier.
     * @param string $userId   Employee who triggered the change.
     * @param string $comment  Optional note appended to the history entry.
     * @return bool True if both the update and the history entry succeeded.
     */
    public function updateStatus(int $orderId, int $statusId, string $userId, string $comment = ''): bool
    {
        try {
            self::getDb()->beginTransaction();

            $query = "
            UPDATE orders SET status_id = ? WHERE id = ?";

            $stmt = self::getDb()->prepare($query);
            $stmt->execute([$statusId, $orderId]);
            if ($stmt->rowCount() !== 1) {
                throw new PDOException('Failed to update order status.');
            }

            $this->addStatusHistory($orderId, $statusId, $userId, $comment);

            self::getDb()->commit();

            return true;
        } catch (\Exception $e) {
            self::getDb()->rollBack();
            error_log('Order status update error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Cancels an order by an employee, recording the reason and contact method.
     *
     * Sets status to cancelled (id = 8), stores cancellation_reason, cancelled_by,
     * and contact_method on the order row, then appends a descriptive history entry —
     * all within a single transaction.
     *
     * @param int    $orderId Order to cancel.
     * @param array  $user    Employee data (id, nom, prenom).
     * @param array  $data    POST data with cancellation_reason and contact_method.
     * @return bool True if the cancellation and history entry succeeded.
     */
    public function cancelByEmploye(int $orderId, array $user, array $data): bool
    {
        try {
            self::getDb()->beginTransaction();

            $query = "
                UPDATE orders SET status_id = 8, cancellation_reason = ?, cancelled_by = ?, contact_method = ?
                WHERE id = ?
            ";
            $stmt = self::getDb()->prepare($query);
            $stmt->execute([
                $data['cancellation_reason'],
                $user['id'],
                $data['contact_method'],
                $orderId
            ]);
            if ($stmt->rowCount() !== 1) {
                throw new PDOException('Failed to cancel the order.');
            }

            $com = $user['prenom'] . ' ' . $user['nom'] . ' a annulé la commande pour la raison suivante: ' . $data['cancellation_reason'];
            $this->addStatusHistory($orderId, 8, $user['id'], $com);

            self::getDb()->commit();

            return true;
        } catch (\Exception $e) {
            self::getDb()->rollBack();
            error_log('Employee order cancellation error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Updates the editable fields of an existing order.
     *
     * Covers logistics and pricing fields: nb_people, delivery_address, delivery_city,
     * delivery_date, delivery_time, menu_price, delivery_cost, total_price, reduction.
     * Does not touch status or history — call updateStatus() for status changes.
     *
     * @param int   $orderId Order to update.
     * @param array $data    Associative array of the fields listed above.
     * @return bool True if exactly one row was updated.
     */
    public function update(int $orderId, array $data): bool
    {
        $query = "
            UPDATE orders SET
            nb_people = ?,
            delivery_address = ?,
            delivery_city = ?,
            delivery_date = ?,
            delivery_time = ?,
            menu_price = ?,
            delivery_cost = ?,
            total_price = ?,
            reduction = ?
            WHERE id = ?
            AND status_id = 1
        ";
        $stmt = self::getDb()->prepare($query);
        $stmt->execute([
            $data['nb_people'],
            $data['delivery_address'],
            $data['delivery_city'],
            $data['delivery_date'],
            $data['delivery_time'],
            $data['menu_price'],
            $data['delivery_cost'],
            $data['total_price'],
            $data['reduction'],
            $orderId,
        ]);

        return $stmt->rowCount() === 1;
    }

    /**
     * Marks material as loaned and computes the return deadline (10 business days from today).
     *
     * @param int $orderId
     * @return bool True if the row was updated.
     */
    public function setMaterialLoaned(int $orderId): bool
    {
        $deadline = new \DateTime();
        $daysAdded = 0;

        while ($daysAdded < 10) {
            $deadline->modify('+1 day');
            $dayOfWeek = (int) $deadline->format('N');
            if ($dayOfWeek < 6) {
                $daysAdded++;
            }
        }

        $query = "UPDATE orders 
              SET material_loaned = TRUE, 
                  material_return_deadline = :deadline 
              WHERE id = :id";
        $stmt = self::getDb()->prepare($query);
        $stmt->execute([
            'deadline' => $deadline->format('Y-m-d H:i:s'),
            'id'       => $orderId
        ]);
        return $stmt->rowCount() === 1;
    }
}
