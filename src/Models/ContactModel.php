<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\DatabaseConnection;
use PDO;

/**
 * Data-access layer for the contacts table.
 */
class ContactModel
{
    /**
     * Returns the shared PDO instance.
     */
    private static function getDb(): PDO
    {
        return DatabaseConnection::getInstance();
    }

    /**
     * Returns all processed contact messages, joined with the name of the user who processed them.
     *
     * @return array Rows with contact fields plus nom and prenom from users.
     */
    public function findProcessed(): array
    {
        $query = "
            SELECT c.*, u.nom, u.prenom
            FROM contacts AS c
            JOIN users AS u ON u.id = c.processed_by
        ";

        $stmt = self::getDb()->prepare($query);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Returns all contact messages that have not yet been processed (processed = 0).
     *
     * @return array Rows with id, email, title, and message.
     */
    public function findToProcessed(): array
    {
        $query = "
            SELECT id, email, title, message
            FROM contacts
            WHERE processed = 0
        ";

        $stmt = self::getDb()->prepare($query);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Inserts a new contact message.
     *
     * @param array $data Associative array with email, title, and message keys.
     * @return bool True if exactly one row was inserted.
     */
    public function create(array $data): bool
    {
        $query = "
            INSERT INTO contacts (email, title, message)
            VALUES (:email, :title, :message)
        ";

        $stmt = self::getDb()->prepare($query);
        $stmt->execute([
            'email'   => $data['email'],
            'title'   => $data['title'],
            'message' => $data['message']
        ]);

        return $stmt->rowCount() === 1;
    }
}
