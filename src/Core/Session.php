<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Secure session manager.
 *
 * HTTPOnly, SameSite Strict cookie, session expires after 30 minutes of inactivity.
 * All methods are static and assume Session::start() has been called beforehand
 * (via index.php).
 */
class Session
{
    private static bool $started = false;

    /**
     * Starts the session and configures cookie options.
     *
     * Automatically destroys the session and redirects to /login
     * if inactivity exceeds 30 minutes.
     */
    public static function start(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {

            $secure = ($_ENV['APP_ENV'] === 'production');

            $cookie = [
                'lifetime' => 3600,
                'path'     => '/',
                'domain'   => '',
                'samesite' => 'Strict',
                'httponly' => true,
                'secure'   => $secure
            ];

            session_set_cookie_params($cookie);
            session_start();
            self::$started = true;

            if (isset($_SESSION['last_activity'])) {
                $inactive = time() - $_SESSION['last_activity'];

                if ($inactive > 1800) {
                    self::destroy();
                    session_start();
                    self::setFlash('Votre session a expiré pour des raisons de sécurité.', 'error');
                    header('Location: /login');
                    exit;
                }
            }

            $_SESSION['last_activity'] = time();
        }
    }

    /**
     * Stores a value in the session.
     *
     * @param string $key   Session key
     * @param mixed  $value Value to store
     */
    public static function set(string $key, mixed $value): void
    {
        if (self::$started) {
            $_SESSION[$key] = $value;
        }
    }

    /**
     * Returns a session value, or $default if not set.
     *
     * @param string $key     Session key
     * @param mixed  $default Fallback value
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        if (!self::$started) {
            return $default;
        }

        return $_SESSION[$key] ?? $default;
    }

    /**
     * Returns true if the given key exists in the session.
     */
    public static function has(string $key): bool
    {
        if (self::$started) {
            return isset($_SESSION[$key]);
        }

        return false;
    }

    /**
     * Removes a session entry.
     */
    public static function delete(string $key): void
    {
        if (self::$started && isset($_SESSION[$key])) {
            unset($_SESSION[$key]);
        }
    }

    /**
     * Destroys the session and invalidates the client cookie.
     */
    public static function destroy(): void
    {
        if (self::$started) {
            $_SESSION = [];
            $params = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires'  => time() - 3600,
                'path'     => $params['path'],
                'domain'   => $params['domain'],
                'secure'   => $params['secure'],
                'httponly' => $params['httponly'],
                'samesite' => $params['samesite'] ?? 'Strict'
            ]);
            session_destroy();
            self::$started = false;
        }
    }

    /**
     * Regenerates the session ID (call after privilege elevation).
     */
    public static function regenerate(): void
    {
        if (self::$started) {
            session_regenerate_id(true);
        }
    }

    /**
     * Stores a flash message in $_SESSION['flash'].
     *
     * Only one flash message exists at a time — the next one overwrites the previous.
     *
     * @param string $message Message text
     * @param string $type    Visual type: 'success' or 'error'
     */
    public static function setFlash(string $message, string $type): void
    {
        if (self::$started) {
            $_SESSION['flash'] = [
                'message' => $message,
                'type'    => $type
            ];
        }
    }

    /**
     * Reads and removes the current flash message.
     *
     * @return array{message: string, type: string}|null
     */
    public static function getFlash(): ?array
    {
        if (self::$started && isset($_SESSION['flash'])) {
            $value = $_SESSION['flash'];
            unset($_SESSION['flash']);
            return $value;
        }
        return null;
    }

    /**
     * Returns true if a user is authenticated (non-empty session user).
     */
    public static function isAuthenticated(): bool
    {
        return isset($_SESSION['user']) && is_array($_SESSION['user']) && !empty($_SESSION['user']);
    }

    /**
     * Stores the authenticated user in the session (password excluded).
     *
     * @param array $user User data from the database
     */
    public static function setUser(array $user): void
    {
        if (isset($user['password'])) {
            unset($user['password']);
        }
        self::set('user', $user);
    }

    /**
     * Returns the authenticated user's data, or null if not logged in.
     *
     * @return array|null
     */
    public static function getUser(): ?array
    {
        if (!self::isAuthenticated()) {
            return null;
        }

        return self::get('user');
    }
}
