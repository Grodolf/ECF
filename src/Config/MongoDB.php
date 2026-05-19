<?php

declare(strict_types=1);

namespace App\Config;

/**
 * MongoDB connection configuration provider.
 *
 * Reads connection parameters from environment variables populated by Env::loadEnv().
 * Falls back to localhost:27017 when MONGO_HOST / MONGO_PORT are not set.
 */
class MongoDB
{
    /**
     * Returns the MongoDB connection configuration array.
     *
     * @return array Keys: host, port, user, password, database.
     */
    public static function getConfig(): array
    {
        return [
            'host'     => $_ENV['MONGO_HOST'] ?? 'localhost',
            'port'     => (int) ($_ENV['MONGO_PORT'] ?? 27017),
            'user'     => $_ENV['MONGO_USER'],
            'password' => $_ENV['MONGO_PASS'],
            'database' => $_ENV['MONGO_DATABASE'],
        ];
    }
}
