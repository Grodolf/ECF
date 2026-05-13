<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\DatabaseConnection;
use PDO;

/**
 * Data-access layer for dishes, their types, and allergen associations.
 */
class DishModel
{
    /**
     * Returns the shared PDO instance.
     */
    private static function getDb(): PDO
    {
        return DatabaseConnection::getInstance();
    }

    /**
     * Returns all dishes joined with their type name and type id.
     *
     * @return array Dish rows with active flag and type information.
     */
    public function findAll(): array
    {
        $query = "
                SELECT d.id, d.name, d.active, td.name AS type, td.id as type_id
                FROM dishes AS d
                JOIN dish_types AS td ON d.dish_type_id = td.id
            ";

        $stmt = self::getDb()->prepare($query);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Returns all allergens for every dish, with dish_id as a grouping key.
     *
     * Results are flat rows ordered by dish id. The controller groups them into
     * a per-dish lookup map to avoid N+1 queries when rendering the list.
     *
     * @return array Rows with dish_id, allergen name, and description.
     */
    public function getAllergenesForDish(): array
    {
        $query = "
            SELECT d.id AS dish_id, a.name, a.description
            FROM dish_allergenes AS da
            JOIN dishes AS d ON da.dish_id = d.id
            JOIN allergenes AS a ON da.allergene_id = a.id
            ORDER BY d.id
        ";

        $stmt = self::getDb()->prepare($query);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Inserts a new dish and returns its auto-generated id.
     *
     * Expected $data keys: name, description, type (dish_type_id), url (image_url).
     *
     * @param array $data Dish fields.
     * @return int|false New dish id on success, false if the insert affected no rows.
     */
    public function create(array $data): int|false
    {
        $query = "
            INSERT INTO dishes (name, description, dish_type_id, image_url, active)
            VALUES (:name, :description, :dish_type_id, :image_url, 1)
        ";

        $stmt = self::getDb()->prepare($query);
        $stmt->execute([
            'name'         => $data['name'],
            'description'  => $data['description'],
            'dish_type_id' => $data['type'],
            'image_url'    => $data['url'],
        ]);

        if ($stmt->rowCount() !== 1) {
            return false;
        }

        return (int) self::getDb()->lastInsertId();
    }

    /**
     * Inserts rows into the dish_allergenes pivot for the given dish.
     *
     * Designed to be called after create(), or after deleteAllergenesByDishID()
     * during an update to replace the full allergen set.
     *
     * @param int   $dishId       Dish to associate allergens with.
     * @param array $allergeneIds List of allergen primary keys to insert.
     */
    public function addAllergenes(int $dishId, array $allergeneIds): void
    {
        $query = "INSERT INTO dish_allergenes (dish_id, allergene_id) VALUES (:dish_id, :allergene_id)";
        $stmt  = self::getDb()->prepare($query);

        foreach ($allergeneIds as $allergeneId) {
            $stmt->execute([
                'dish_id' => $dishId,
                'allergene_id' => $allergeneId
                ]);
        }
    }

    /**
     * Finds a single dish by primary key, joined with its type.
     *
     * @param int $id Dish identifier.
     * @return array|null Dish row, or null if not found.
     */
    public function findById(int $id): ?array
    {
        $query = "
            SELECT
            d.id, d.name, d.description, d.dish_type_id, d.image_url, d.active,
            t.name AS type_name, t.id AS type_id
            FROM dishes AS d
            JOIN dish_types AS t ON d.dish_type_id = t.id
            WHERE d.id = ?
        ";

        $stmt = self::getDb()->prepare($query);
        $stmt->execute([$id]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Returns all allergens associated with a specific dish.
     *
     * Used by the edit form to know which allergens to pre-select.
     *
     * @param int $id Dish identifier.
     * @return array Rows with allergen id and name.
     */
    public function getAllergenesByDishId(int $id): array
    {
        $query = "
            SELECT id, name
            FROM allergenes
            JOIN dish_allergenes AS da ON allergenes.id = da.allergene_id
            WHERE da.dish_id = ?
        ";

        $stmt = self::getDb()->prepare($query);
        $stmt->execute([$id]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Updates the name, description, and image of an existing dish.
     *
     * Expected $data keys: name, description, url (image_url), id.
     * Note: dish_type_id is not updated here; use a dedicated method if needed.
     *
     * @param array $data Fields to update, including the dish id.
     * @return bool True if exactly one row was updated.
     */
    public function update(array $data): bool
    {
        $query = "
            UPDATE dishes SET
            name = :name, description = :description, image_url = :image_url
            WHERE id = :id
        ";

        $stmt = self::getDb()->prepare($query);
        $stmt->execute([
            'name'         => $data['name'],
            'description'  => $data['description'],
            'image_url'    => $data['url'],
            'id'           => $data['id']
        ]);

        return $stmt->rowCount() === 1;
    }

    /**
     * Removes all allergen associations for a dish from the pivot table.
     *
     * Called before addAllergenes() during an update to replace the full set.
     *
     * @param int $id Dish identifier.
     */
    public function deleteAllergenesByDishID(int $id)
    {
        $query = "
            DELETE FROM dish_allergenes
            WHERE dish_id = ?
        ";

        $stmt = self::getDb()->prepare($query);
        $stmt->execute([$id]);
    }

    /**
     * Toggles a dish's active status (active ↔ inactive).
     *
     * @param int $dishId Dish identifier.
     * @return bool True if exactly one row was updated.
     */
    public function toggle(int $dishId): bool
    {
        $query = "UPDATE dishes SET active = NOT active WHERE id = ?";

        $stmt = self::getDb()->prepare($query);
        $stmt->execute([$dishId]);

        return $stmt->rowCount() === 1;
    }

    /**
     * Returns all available dish types.
     *
     * @return array Rows with id and name.
     */
    public function getAllDishTypes(): array
    {
        $query = "SELECT * FROM dish_types";

        $stmt = self::getDb()->prepare($query);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Returns all available allergens.
     *
     * @return array Rows with id and name.
     */
    public function getAllAllergenes(): array
    {
        $query = "SELECT * FROM allergenes";

        $stmt = self::getDb()->prepare($query);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
