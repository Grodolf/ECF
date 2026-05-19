<?php

declare(strict_types=1);

namespace App\Config;

/**
 * MySQL database configuration provider.
 *
 * Reads connection parameters from environment variables populated by Env::loadEnv().
 */
class Database
{
    /**
     * Returns the database connection configuration array.
     *
     * @return array Keys: host, port, dbname, username, password, ssl, ca_cert, charset.
     */
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
