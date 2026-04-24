<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\DatabaseConnection;
use App\Core\Security;
use PDO;

class MenuModel
{
    private static PDO $db;

    private static function getDb(): PDO
    {
        return DatabaseConnection::getInstance();
    }

    public function findAll(bool $isactiveOnly = true): array
    {
        self::$db = self::getDb();

        $query = "
                SELECT m.id, m.title, m.description, m.min_people, m.base_price,
                t.name AS theme, t.id AS theme_id, r.name AS regime, r.id AS regime_id
                FROM menus AS m
                JOIN themes AS t ON m.theme_id = t.id
                JOIN regimes AS r ON m.regime_id = r.id";

        $params = [];

        if ($isactiveOnly) {
            $query .= " WHERE m.active = ?";
            $params[] = 1;
        }

        $stmt = self::$db->prepare($query);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);

    }

    public function getListImages(): array
    {
        self::$db = self::getDb();

        $query = "SELECT menu_id, image_url, alt_text FROM menu_images WHERE display_order = 1";
        $stmt = self::$db->prepare($query);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById(int $id, bool $isactiveOnly = true): array
    {
        self::$db = self::getDb();

        $query = "SELECT m.id, m.title, m.description, m.min_people, m.base_price, m.conditions, m.stock, t.name AS theme_name, r.name AS regime_name
                FROM menus AS m
                JOIN themes AS t ON m.theme_id = t.id
                JOIN regimes AS r ON m.regime_id = r.id
                WHERE m.active = ? AND m.id = ?";
        $stmt = self::$db->prepare($query);
        $stmt->execute([$isactiveOnly, $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ? $result : [];
    }

    public function getMenuImages(int $id): array
    {
        self::$db = self::getDb();

        $query = "SELECT id, image_url, alt_text FROM menu_images WHERE menu_id = ? ORDER BY display_order";
        $stmt = self::$db->prepare($query);
        $stmt->execute([$id]);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $result ? $result : [];
    }

    public function getMenuDishes(int $id): array
    {
        self::$db = self::getDb();

        $query = "SELECT d.id, d.name, d.description, d.image_url,
                t.name AS dish_type_name
                FROM menu_dishes AS m
                JOIN dishes AS d ON m.dish_id = d.id
                JOIN dish_types AS t ON d.dish_type_id = t.id
                WHERE menu_id = ?
                ORDER BY t.display_order";
        $stmt = self::$db->prepare($query);
        $stmt->execute([$id]);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $result ? $result : [];
    }

    public function getDishAllergenes(int $id): array
    {
        self::$db = self::getDb();

        $query = "SELECT a.name, a.description
                FROM dish_allergenes AS d
                JOIN allergenes AS a ON d.allergene_id = a.id
                WHERE d.dish_id = ?";
        $stmt = self::$db->prepare($query);
        $stmt->execute([$id]);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $result ? $result : [];
    }

    public function getAllergenesForMenu(int $id): array
    {
        self::$db = self::getDb();

        $query = "
        SELECT d.id AS dish_id,
               a.name AS allergene_name,
               a.description AS allergene_description
        FROM menu_dishes AS md
        JOIN dishes AS d ON md.dish_id = d.id
        JOIN dish_allergenes AS da ON d.id = da.dish_id
        JOIN allergenes AS a ON da.allergene_id = a.id
        WHERE md.menu_id = ?
        ORDER BY d.id";

        $stmt = self::$db->prepare($query);
        $stmt->execute([$id]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findFiltered(array $filters): array
    {
        self::$db = self::getDb();

        $query = "
        SELECT m.id, m.title, m.description, m.min_people, m.base_price,
               m.theme_id, m.regime_id,
               t.name AS theme, r.name AS regime
        FROM menus AS m
        JOIN themes AS t ON m.theme_id = t.id
        JOIN regimes AS r ON m.regime_id = r.id
        WHERE m.active = 1
    ";

        $params = [];

        if (!empty($filters['min_price'])) {
            $query .= " AND m.base_price >= ?";
            $params[] = (float) $filters['min_price'];
        }

        if (!empty($filters['max_price'])) {
            $query .= " AND m.base_price <= ?";
            $params[] = (float) $filters['max_price'];
        }

        if (!empty($filters['min_people'])) {
            $query .= " AND m.min_people <= ?";
            $params[] = (int) $filters['min_people'];
        }

        if (!empty($filters['theme'])) {
            $query .= " AND m.theme_id = ?";
            $params[] = (int) $filters['theme'];
        }

        if (!empty($filters['regime'])) {
            $query .= " AND m.regime_id = ?";
            $params[] = (int) $filters['regime'];
        }

        $stmt = self::$db->prepare($query);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAvailableOptions(array $filters): array
    {
        self::$db = self::getDb();

        // Requête de base (même logique que findFiltered)
        $baseQuery = "
        SELECT m.theme_id, m.regime_id
        FROM menus AS m
        WHERE m.active = 1
    ";

        $params = [];

        // Appliquer les mêmes filtres que findFiltered
        if (!empty($filters['min_price'])) {
            $baseQuery .= " AND m.base_price >= ?";
            $params[] = (float) $filters['min_price'];
        }

        if (!empty($filters['max_price'])) {
            $baseQuery .= " AND m.base_price <= ?";
            $params[] = (float) $filters['max_price'];
        }

        if (!empty($filters['min_people'])) {
            $baseQuery .= " AND m.min_people <= ?";
            $params[] = (int) $filters['min_people'];
        }

        // NE PAS filtrer par theme si on veut savoir quels thèmes sont disponibles
        // NE PAS filtrer par regime si on veut savoir quels régimes sont disponibles

        $stmt = self::$db->prepare($baseQuery);
        $stmt->execute($params);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Extraire les IDs uniques de thèmes et régimes disponibles
        $availableThemes = array_values(array_unique(array_column($results, 'theme_id')));
        $availableRegimes = array_values(array_unique(array_column($results, 'regime_id')));

        return [
            'themes' => $availableThemes,
            'regimes' => $availableRegimes
        ];
    }

}
