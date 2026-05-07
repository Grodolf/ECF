<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\DatabaseConnection;
use App\Core\Security;
use PDO;

/**
 * Data-access layer for menus, their images, dishes, allergens, and filter queries.
 */
class MenuModel
{
    /**
     * Returns the shared PDO instance.
     */
    private static function getDb(): PDO
    {
        return DatabaseConnection::getInstance();
    }

    /**
     * Returns all menus joined with their theme and regime.
     *
     * @param bool $isactiveOnly When true (default), only returns menus with active = 1.
     * @return array List of menu rows with theme and regime names.
     */
    public function findAll(bool $isactiveOnly = true): array
    {
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

        $stmt = self::getDb()->prepare($query);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);

    }

    /**
     * Returns the first cover image (display_order = 1) for every menu.
     *
     * Designed to be merged with a menu list via MenuController::imagesByMenuId()
     * in a single pass rather than N+1 queries.
     *
     * @return array Rows with menu_id, image_url, and alt_text.
     */
    public function getListImages(): array
    {
        $query = "SELECT menu_id, image_url, alt_text FROM menu_images WHERE display_order = 1";
        $stmt = self::getDb()->prepare($query);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Finds a single menu by primary key, joined with theme and regime.
     *
     * Also returns stock and conditions, unlike findAll().
     *
     * @param int  $id           Menu identifier.
     * @param bool $isactiveOnly When true (default), only matches menus with active = 1.
     * @return array|null Menu row, or null if not found or inactive.
     */
    public function findById(int $id, bool $isactiveOnly = true): ?array
    {
        $query = "SELECT m.id, m.title, m.description, m.min_people, m.base_price, m.conditions, m.stock, t.name AS theme_name, r.name AS regime_name
                FROM menus AS m
                JOIN themes AS t ON m.theme_id = t.id
                JOIN regimes AS r ON m.regime_id = r.id
                WHERE m.id = ?";

        if ($isactiveOnly) {
            $query .= " AND m.active = 1";
        }

        $stmt = self::getDb()->prepare($query);
        $stmt->execute([$id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    /**
     * Returns all images for a menu, ordered by display_order.
     *
     * Used on the detail page to populate the carousel.
     *
     * @param int $id Menu identifier.
     * @return array Rows with id, image_url, and alt_text.
     */
    public function getMenuImages(int $id): array
    {
        $query = "SELECT id, image_url, alt_text FROM menu_images WHERE menu_id = ? ORDER BY display_order";
        $stmt = self::getDb()->prepare($query);
        $stmt->execute([$id]);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    /**
     * Returns all dishes belonging to a menu, ordered by dish type display order.
     *
     * Each row includes dish id, name, description, image_url, and dish_type_name.
     * Allergens are fetched separately via getAllergenesForMenu() and injected by
     * the controller to avoid a cartesian product.
     *
     * @param int $id Menu identifier.
     * @return array Dish rows ordered by dish_types.display_order.
     */
    public function getMenuDishes(int $id): array
    {
        $query = "SELECT d.id, d.name, d.description, d.image_url,
                t.name AS dish_type_name
                FROM menu_dishes AS m
                JOIN dishes AS d ON m.dish_id = d.id
                JOIN dish_types AS t ON d.dish_type_id = t.id
                WHERE menu_id = ?
                ORDER BY t.display_order";
        $stmt = self::getDb()->prepare($query);
        $stmt->execute([$id]);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    /**
     * Returns all allergens for every dish in a menu, keyed by dish.
     *
     * Results are flat rows with dish_id, allergene_name, and allergene_description.
     * The controller groups them by dish_id before passing to the view.
     *
     * @param int $id Menu identifier.
     * @return array Flat allergen rows ordered by dish id.
     */
    public function getAllergenesForMenu(int $id): array
    {
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

        $stmt = self::getDb()->prepare($query);
        $stmt->execute([$id]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Returns active menus matching the given filter criteria.
     *
     * All filters are optional and additive (AND). Accepted keys:
     * - min_price (float): minimum base_price
     * - max_price (float): maximum base_price
     * - min_people (int):  menus that accommodate at most this many people
     *                      (i.e. min_people <= requested value)
     * - theme (int):       theme_id to match
     * - regime (int):      regime_id to match
     *
     * @param array $filters Associative array of filter values (empty values are ignored).
     * @return array Matching menu rows with theme and regime names.
     */
    public function findFiltered(array $filters): array
    {
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

        $stmt = self::getDb()->prepare($query);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Returns the theme and regime IDs still available given the current price/people filters.
     *
     * Applies the same price and min_people constraints as findFiltered() but deliberately
     * omits theme and regime filters, so the UI can show which options remain selectable
     * rather than collapsing to an empty set when both a theme and a regime are active.
     *
     * @param array $filters Associative array with optional keys: min_price, max_price, min_people.
     * @return array{themes: int[], regimes: int[]} Unique available theme and regime IDs.
     */
    public function getAvailableOptions(array $filters): array
    {
        $baseQuery = "
        SELECT m.theme_id, m.regime_id
        FROM menus AS m
        WHERE m.active = 1
    ";

        $params = [];

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

        $stmt = self::getDb()->prepare($baseQuery);
        $stmt->execute($params);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $availableThemes = array_values(array_unique(array_column($results, 'theme_id')));
        $availableRegimes = array_values(array_unique(array_column($results, 'regime_id')));

        return [
            'themes' => $availableThemes,
            'regimes' => $availableRegimes
        ];
    }

}
