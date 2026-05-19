<?php

declare(strict_types=1);

namespace App\Core;

use App\Config\MongoDB;
use MongoDB\Client;
use MongoDB\Database;
use MongoDB\Driver\Exception\ConnectionException;

/**
 * MongoDB singleton returning a ready-to-use Database handle.
 */
class MongoDBConnection
{
    private static ?Database $instance = null;

    /**
     * Returns the unique MongoDB\Database instance, creating it if necessary.
     *
     * @throws ConnectionException If the connection fails.
     */
    public static function getInstance(): Database
    {
        if (self::$instance === null) {
            $config = MongoDB::getConfig();

            $uri = $config['uri'];

            try {
                $client = new Client($uri);
                self::$instance = $client->selectDatabase($config['database']);
            } catch (ConnectionException $e) {
                error_log('MongoDB connection failed: ' . $e->getMessage());
                http_response_code(503);
                die('Service temporarily unavailable.');
            }
        }

        return self::$instance;
    }
}
