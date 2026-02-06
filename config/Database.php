<?php

declare(strict_types=1);

namespace App\Config;

class Database
{
    public static function getConfig(): array
    {

        return [
            'host'     => $_ENV['MARIADB_HOST'],
            'port'     => $_ENV['MARIADB_PORT'],
            'dbname'   => $_ENV['MARIADB_DATABASE'],
            'username' => $_ENV['MARIADB_USER'],
            'password' => $_ENV['MARIADB_PASSWORD'],
            'charset'  => 'utf8mb4'
            ];
    }
}
