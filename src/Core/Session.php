<?php

declare(strict_types=1);

namespace App\Core;

class Session
{
    private static bool $started = false;

    public static function start(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {

            if ($_ENV['APP_ENV'] === 'production') {
                $secure = true;
            } else {
                $secure = false;
            }

            $cookie = [
                'lifetime' => 3600,
                'path' => '/',
                'domain' => '',
                'samesite' => 'Strict',
                'httponly' => true,
                'secure' => $secure
                ];

            session_set_cookie_params($cookie);
            session_start();
            self::$started = true;

            if (isset($_SESSION['last_activity'])) {
                $inactive = time() - $_SESSION['last_activity'];

                if ($inactive > 1800) {
                    self::destroy();
                    self::setFlash('warning', 'Votre session a expiré pour des raisons de sécurité.');
                    header('Location: /login');
                    exit;
                }
            }

            $_SESSION['last_activity'] = time();
        }
    }

    public static function set($key, $value): void
    {
        if (self::$started) {
            $_SESSION[$key] = $value;
        }
    }

    public static function get($key, $default = null): mixed
    {
        if (!self::$started) {
            $default = 'Pas de session en cours.';
        }

        if (isset($_SESSION[$key])) {
            return $_SESSION[$key];
        }

        return $default;
    }

    public static function has($key): bool
    {
        if (self::$started) {
            return isset($_SESSION[$key]);
        }

        return false;
    }

    public static function delete($key): void
    {
        if (self::$started && isset($_SESSION[$key])) {
            unset($_SESSION[$key]);
        }
    }

    public static function destroy(): void
    {
        if (self::$started) {
            $_SESSION = [];
            $params = session_get_cookie_params();
            $params['lifetime'] = -3600;
            setcookie('PHPSESSIONID', $_COOKIE['PHPSESSIONID'], $params);
            session_destroy();
            self::$started = false;
        }
    }

    public static function regenerate(): void
    {
        if (self::$started) {
            session_regenerate_id(true);
        }
    }

    public static function setFlash($key, $message, $type = 'info'): void
    {
        if (self::$started) {
            $_SESSION['flash'][$key] = [
                'message' => $message,
                'type' => $type
            ];
        }
    }

    public static function getFlash($key): mixed
    {
        if (self::$started && isset($_SESSION['flash'][$key])) {
            $value = $_SESSION['flash'][$key];
            unset($_SESSION['flash'][$key]);
            return $value;
        }
        return null;
    }

    /**
     * TODO
     *
     * Après userController
     *
     * isAuthenticted()
     * getUser()
     * setUser
     */
}
