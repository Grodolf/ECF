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
     * @return array Keys: uri, database.
     */
    public static function getConfig(): array
    {
        return [
            'uri'      => $_ENV['MONGO_URI'],
            'database' => $_ENV['MONGO_DATABASE'],
        ];
    }
}
