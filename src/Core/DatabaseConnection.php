<?php

declare(strict_types=1);

namespace App\Core;

use App\Config\Database;
use PDO;
use PDOException;

/**
 * PDO singleton for the MySQL connection.
 *
 * Automatically configures SSL when the `ssl = REQUIRED` option is present
 * in the database configuration (Aiven production environment).
 */
class DatabaseConnection
{
    private static ?PDO $instance = null;

    /**
     * Returns the unique PDO instance, creating it if necessary.
     *
     * @throws PDOException If the connection fails or the SSL certificate is missing
     */
    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            $config = Database::getConfig();

            $dsn = sprintf(
                "mysql:host=%s;port=%s;dbname=%s;charset=%s",
                $config['host'],
                $config['port'],
                $config['dbname'],
                $config['charset']
            );

            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false
            ];

            if (isset($config['ssl']) && $config['ssl'] === 'REQUIRED') {
                if (!empty($config['ca_cert']) && file_exists($config['ca_cert'])) {
                    $options[PDO::MYSQL_ATTR_SSL_CA] = $config['ca_cert'];
                } else {
                    throw new PDOException("SSL CA certificate not found or not configured");
                }
            }

            try {
                self::$instance = new PDO(
                    $dsn,
                    $config['username'],
                    $config['password'],
                    $options
                );
            } catch (PDOException $e) {
                error_log('DB connection failed: ' . $e->getMessage());
                http_response_code(503);
                die('Service temporarily unavailable.');
            }
        }

        return self::$instance;
    }
}
