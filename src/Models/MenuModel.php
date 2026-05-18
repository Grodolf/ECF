<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\DatabaseConnection;
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
                SELECT m.id, m.title, m.description, m.min_people, m.base_price, stock, active,
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
        $query = "SELECT m.*, t.name AS theme_name, r.name AS regime_name
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
        $query = "SELECT * FROM menu_images WHERE menu_id = ? ORDER BY display_order";
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
        $query = "SELECT d.*, t.name AS dish_type_name, t.id AS type_id
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
     * Inserts a new menu into the database.
     *
     * @param array $data Menu data (title, description, theme, regime, min_people, price, conditions)
     * @return int|false The new menu ID on success, false on failure
     */
    public function create(array $data): int|false
    {
        $query = "
            INSERT INTO menus (title, description, theme_id, regime_id, min_people, base_price, conditions, stock, active)
            VALUES (:title, :description, :theme_id, :regime_id, :min_people, :base_price, :conditions, 0, 1)
        ";

        $stmt = self::getDb()->prepare($query);
        $stmt->execute([
            'title'         => $data['title'],
            'description'  => $data['description'],
            'theme_id' => $data['theme'],
            'regime_id' => $data['regime'],
            'min_people' => $data['min_people'],
            'base_price' => $data['price'],
            'conditions' => $data['conditions'] ?? null
        ]);

        if ($stmt->rowCount() !== 1) {
            return false;
        }

        return (int) self::getDb()->lastInsertId();
    }

    /**
     * Links dishes to a menu in the menu_dishes pivot table.
     *
     * @param int   $menuId  Menu ID
     * @param array $dishIds List of dish IDs to associate
     */
    public function addDishes(int $menuId, array $dishIds): void
    {
        $query = "INSERT INTO menu_dishes (menu_id, dish_id) VALUES (:menu_id, :dish_id)";
        $stmt  = self::getDb()->prepare($query);

        foreach ($dishIds as $dishId) {
            $stmt->execute([
                'menu_id' => $menuId,
                'dish_id' => $dishId
                ]);
        }
    }

    /**
     * Removes all dish associations for a menu from the pivot table.
     *
     * @param int $id Menu identifier.
     */
    public function deleteDishes(int $id): void
    {
        $query = "DELETE FROM menu_dishes WHERE menu_id = ?";

        $stmt = self::getDb()->prepare($query);
        $stmt->execute([$id]);
    }

    /**
     * Updates the core fields of an existing menu.
     *
     * Expected $data keys: id, title, description, theme_id, regime_id,
     * min_people, base_price, conditions.
     * Returns true even when rowCount() = 0 (no column value changed).
     *
     * @param array $data Menu fields including the menu id.
     * @return bool True on success (including no-op updates).
     */
    public function update(array $data): bool
    {
        $query = "
            UPDATE menus SET
            title = :title, description = :description, theme_id = :theme_id, regime_id = :regime_id,
            min_people = :min_people, base_price = :base_price, conditions = :conditions
            WHERE id = :id
        ";

        $stmt = self::getDb()->prepare($query);
        $stmt->execute([
            'title'       => $data['title'],
            'description' => $data['description'],
            'theme_id'    => $data['theme_id'],
            'regime_id'   => $data['regime_id'],
            'min_people'  => $data['min_people'],
            'base_price'  => $data['base_price'],
            'conditions'  => $data['conditions'],
            'id'          => $data['id']
        ]);

        // rowCount() returns 0 when no column value changed — treat that as success too
        return $stmt->rowCount() >= 0;
    }

    /**
     * Saves images for a menu with generated alt text and display order.
     *
     * @param int    $menuId    Menu ID
     * @param string $title     Menu title, used to build the alt attribute
     * @param array  $imageUrls List of image URLs to save
     */
    public function addImages(int $menuId, string $title, array $imageUrls): void
    {
        $query = "INSERT INTO menu_images (menu_id, image_url, alt_text, display_order) VALUES (:menu_id, :image_url, :alt_text, :display_order)";
        $stmt  = self::getDb()->prepare($query);

        $i = 1;
        foreach ($imageUrls as $imageUrl) {
            $stmt->execute([
                'menu_id' => $menuId,
                'image_url' => $imageUrl,
                'alt_text' => 'Photo du menu '. $title . ' - ' .$i,
                'display_order' => $i,
                ]);
            $i++;
        }
    }

    /**
     * Deletes a single menu image row by primary key.
     *
     * @param int $id Image row identifier.
     * @return bool True if exactly one row was deleted.
     */
    public function deleteImage(int $id): bool
    {
        $query = "DELETE FROM menu_images WHERE id = ?";

        $stmt = self::getDb()->prepare($query);
        $stmt->execute([$id]);

        return $stmt->rowCount() === 1;
    }

    /**
     * Updates display_order and alt_text for a batch of menu images.
     *
     * @param array $images List of arrays with 'id', 'display_order', and 'alt_text' keys.
     */
    public function updateImageOrder(array $images): void
    {
        $query = "
            UPDATE menu_images SET display_order = :display_order, alt_text = :alt_text
            WHERE id =:id
        ";
        $stmt = self::getDb()->prepare($query);

        foreach ($images as $image) {
            $stmt->execute([
                'display_order' => $image['display_order'],
                'id'            => $image['id'],
                'alt_text'      => $image['alt_text']
            ]);
        }
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

    /**
     * Returns all dishes currently assigned to any menu, with their menu id.
     *
     * Results are flat rows keyed by menu_id. Used by the employee menu management
     * page to build a per-menu dish lookup map without N+1 queries.
     *
     * @return array Rows with menu_id, dish id, and dish name.
     */
    public function getAllDishes(): array
    {
        $query = "
            SELECT
            md.menu_id, d.id, d.name
            FROM menu_dishes AS md
            JOIN dishes AS d ON d.id = md.dish_id
        ";

        $stmt = self::getDb()->prepare($query);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Returns all allergens for every dish assigned to any menu, with their menu id.
     *
     * Results are flat rows keyed by menu_id. The controller groups them into a
     * lookup map to avoid N+1 queries when rendering the management list.
     *
     * @return array Rows with menu_id, allergen id, and allergen name.
     */
    public function getAllAllergenes(): array
    {
        $query = "
            SELECT
            md.menu_id, a.id, a.name
            FROM menu_dishes AS md
            JOIN dish_allergenes AS da ON da.dish_id = md.dish_id
            JOIN allergenes AS a ON a.id = da.allergene_id
        ";

        $stmt = self::getDb()->prepare($query);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Toggles a menu's active status (active ↔ inactive).
     *
     * @param int $id Menu identifier.
     * @return bool True if exactly one row was updated.
     */
    public function toggle(int $id): bool
    {
        $query = "UPDATE menus SET active = NOT active WHERE id = ?";

        $stmt = self::getDb()->prepare($query);
        $stmt->execute([$id]);

        return $stmt->rowCount() === 1;
    }

    /**
     * Increments the stock of a menu by the given quantity.
     *
     * @param int $id       Menu identifier
     * @param int $quantity Amount to add to the current stock
     * @return bool True if exactly one row was updated, false otherwise
     */
    public function addStock(int $id, int $quantity): bool
    {
        $query = "UPDATE menus SET stock = stock + :quantity WHERE id = :id";

        $stmt = self::getDb()->prepare($query);
        $stmt->execute([
            'id'       => $id,
            'quantity' => $quantity
        ]);

        return $stmt->rowCount() === 1;
    }

    /**
     * Returns the current stock value for a menu.
     *
     * @param int $id Menu identifier.
     * @return array Row with a single 'stock' key.
     */
    public function getStock(int $id): array
    {
        $query = "SELECT stock FROM menus WHERE id = ?";

        $stmt = self::getDb()->prepare($query);
        $stmt->execute([$id]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Returns all available themes.
     *
     * @return array Rows with id and name.
     */
    public function getAllThemes(): array
    {
        $query = "SELECT * FROM themes";

        $stmt = self::getDb()->prepare($query);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Returns all available dietary regimes.
     *
     * @return array Rows with id and name.
     */
    public function getAllRegimes(): array
    {
        $query = "SELECT * FROM regimes";

        $stmt = self::getDb()->prepare($query);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
