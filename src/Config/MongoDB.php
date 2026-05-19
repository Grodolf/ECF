<?php

declare(strict_types=1);

namespace App\Config;

class MongoDB
{
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
