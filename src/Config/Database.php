<?php

declare(strict_types=1);

namespace App\Config;

class Database
{
    public static function getConfig(): array
    {

        return [
            'host'     => $_ENV['DB_HOST'],
            'port'     => $_ENV['DB_PORT'],
            'dbname'   => $_ENV['DB_DATABASE'],
            'username' => $_ENV['DB_USER'],
            'password' => $_ENV['DB_PASSWORD'],
            'ssl'      => $_ENV['DB_SSL_MODE'],
            'ca_cert'  => $_ENV['DB_CA_CERT'],
            'charset'  => 'utf8mb4'
            ];
    }
}
