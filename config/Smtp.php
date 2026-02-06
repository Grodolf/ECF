<?php

declare(strict_types=1);

namespace App\Config;

class Smtp
{
    public static function getConfig(): array
    {
        return [
            'host' => $_ENV['SMTP_HOST'],
            'port' => (int)$_ENV['SMTP_PORT'],
            'useAuth' => (bool)$_ENV['SMTP_USE_AUTH'],
            'user' => $_ENV['SMTP_USER'],
            'password' => $_ENV['SMTP_PASSWORD'],
            'fromEmail' => $_ENV['SMTP_FROM_EMAIL'],
            'fromName' => $_ENV['SMTP_FROM_NAME'],
            'debug' => (bool)$_ENV['SMTP_DEBUG'],
        ];
    }
}
