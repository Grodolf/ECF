<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\DatabaseConnection;
use PDO;

/**
 * Data-access layer for reviews.
 */
class ReviewModel
{
    /**
     * Returns the shared PDO instance.
     */
    private static function getDb(): PDO
    {
        return DatabaseConnection::getInstance();
    }

    /**
     * Finds a single review by its associated order ID.
     *
     * @return array|null The review row, or null if none exists for this order.
     */
    public function findByOrderId(int $orderId): ?array
    {
        $query = "SELECT * FROM reviews WHERE order_id = ? ";

        $stmt = self::getDb()->prepare($query);
        $stmt->execute([$orderId]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result !== false ? $result : null;
    }

    /**
     * Finds a single review by its primary key.
     *
     * @return array|null The review row, or null if not found.
     */
    public function findByReviewId(int $reviewId): ?array
    {
        $query = "SELECT * FROM reviews WHERE id = ? ";

        $stmt = self::getDb()->prepare($query);
        $stmt->execute([$reviewId]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result !== false ? $result : null;
    }

    /**
     * Returns all reviews awaiting moderation, joined with user and menu data.
     */
    public function findPending(): array
    {
        $query = "SELECT r.*, u.nom, u.prenom, u.email, m.title
            FROM reviews AS r
            JOIN users AS u ON u.id = r.user_id
            JOIN orders AS o ON o.id = r.order_id
            JOIN menus AS m ON m.id = o.menu_id
            WHERE status = 'pending'
        ";

        $stmt = self::getDb()->prepare($query);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Returns all approved reviews, joined with user and menu data.
     */
    public function findValidated(): array
    {
        $query = "SELECT r.*, u.nom, u.prenom, u.email, m.title
            FROM reviews AS r
            JOIN users AS u ON u.id = r.user_id
            JOIN orders AS o ON o.id = r.order_id
            JOIN menus AS m ON m.id = o.menu_id
            WHERE status = 'approved'
        ";

        $stmt = self::getDb()->prepare($query);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Inserts a new review record with a default status of 'pending'.
     *
     * @param array $data Associative array with order_id, user_id, rating, and comment.
     * @return bool True if exactly one row was inserted.
     */
    public function create(array $data): bool
    {
        $query = "
            INSERT INTO reviews (order_id, user_id, rating, comment)
            VALUES (:order_id, :user_id, :rating, :comment)
        ";

        $stmt = self::getDb()->prepare($query);
        $stmt->execute([
            'order_id' => $data['order_id'],
            'user_id'  => $data['user_id'],
            'rating'   => $data['rating'],
            'comment'  => $data['comment']
        ]);

        return $stmt->rowCount() === 1;
    }

    /**
     * Approves or rejects a review, recording the moderator and timestamp.
     *
     * @param string      $status       'approved' or 'rejected'.
     * @param string      $userId       ID of the employee performing the moderation.
     * @param string|null $rejectReason Required when status is 'rejected', null otherwise.
     * @return bool True if exactly one row was updated.
     */
    public function validate(int $id, string $status, string $userId, ?string $rejectReason): bool
    {
        $query = "UPDATE reviews SET
            status = :status, validated_by = :validated_by, validated_at = :validated_at, reject_reason = :reject_reason
            WHERE id = :id
        ";

        $stmt = self::getDb()->prepare($query);
        $stmt->execute([
            'id' => $id,
            'status' => $status,
            'validated_by' => $userId,
            'validated_at' => date('Y-m-d H:i:s'),
            'reject_reason' => $rejectReason ?? null
        ]);

        return $stmt->rowCount() === 1;
    }
}
