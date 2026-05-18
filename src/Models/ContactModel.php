<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\DatabaseConnection;
use PDO;

class ContactModel
{
    /**
     * Returns the shared PDO instance.
     */
    private static function getDb(): PDO
    {
        return DatabaseConnection::getInstance();
    }

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
