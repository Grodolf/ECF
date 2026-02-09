<?php

namespace App\Core;

use App\Config\Database;
use PDO;
use PDOException;

class DatabaseConnection
{
    private static ?PDO $instance = null;

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
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ];

            // Ajouter les options SSL si nécessaire (Aiven en prod)
            if (isset($config['ssl']) && $config['ssl'] === 'REQUIRED') {
                if (!empty($config['ca_cert']) && file_exists($config['ca_cert'])) {
                    $options[PDO::MYSQL_ATTR_SSL_CA] = $config['ca_cert'];
                } else {
                    throw new PDOException("Certificat SSL CA introuvable ou non configuré");
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
                throw new PDOException("Erreur de connexion à la base de données : " . $e->getMessage());
            }
        }

        return self::$instance;
    }
}
