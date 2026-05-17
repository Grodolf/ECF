<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\DatabaseConnection;
use PDO;

class ScheduleModel
{
    /**
     * Returns the shared PDO instance.
     */
    private static function getDb(): PDO
    {
        return DatabaseConnection::getInstance();
    }

    public function findAll(): array
    {
        $query = "SELECT * FROM schedules ORDER BY id";

        $stmt = self::getDb()->prepare($query);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateAll(array $datas): void
    {
        $query = "
            UPDATE schedules SET
            opening_time = :opening_time,
            closing_time = :closing_time,
            closed = :closed
            WHERE id = :id
        ";

        $stmt = self::getDb()->prepare($query);
        foreach ($datas as $data) {
            $stmt->execute([
                'opening_time' => $data['opening_time'] !== '' ? $data['opening_time'] : null,
                'closing_time' => $data['closing_time'] !== '' ? $data['closing_time'] : null,
                'closed' => $data['closed'],
                'id' => $data['id']
            ]);
        }
    }
}
