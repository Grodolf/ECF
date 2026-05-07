<?php

declare(strict_types=1);

namespace App\Core;

use App\Core\FlashMessage;
use App\Core\Session;

/**
 * Application security utilities.
 *
 * Handles password hashing, CSRF protection, input validation,
 * token generation and role-based access control.
 * All methods are static.
 */
class Security
{
    /**
     * Hashes a password with bcrypt.
     *
     * @param string $password Plain-text password
     * @return string Bcrypt hash
     */
    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT);
    }

    /**
     * Verifies a plain-text password against its bcrypt hash.
     *
     * @param string $password Plain-text password
     * @param string $hash     Stored bcrypt hash
     */
    public static function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    /**
     * Generates a random CSRF token and stores it in the session.
     *
     * @return string 64-character hexadecimal token
     */
    public static function generateCsrfToken(): string
    {
        $existing = Session::get('csrf_token');
        if ($existing) {
            return $existing;
        }
        $token = bin2hex(random_bytes(32));
        Session::set('csrf_token', $token);
        return $token;
    }

    /**
     * Verifies a CSRF token using constant-time comparison.
     *
     * @param string $token Token submitted by the form
     */
    public static function verifyCsrfToken(string $token): bool
    {
        $session_token = Session::get('csrf_token');
        if ($session_token) {
            return hash_equals($session_token, $token);
        } else {
            return false;
        }
    }

    /**
     * Sanitises a string or array of strings (trim + stripslashes + htmlspecialchars).
     *
     * @param string|array $data Raw input
     * @return string|array Sanitised output
     */
    public static function sanitizeInput(string|array $data): string|array
    {
        if (is_array($data)) {
            return array_map([self::class, 'sanitizeInput'], $data);
        }

        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
        return $data;
    }

    /**
     * Escape htmlspecialchars but keep &nbsp
     *
     * @param string $text text input
     * @return string sanitsed text with &nbsp only
     */
    public static function formatText(string $text)
    {
        $parts = explode('&nbsp;', $text);
        $parts = array_map([Security::class, 'escapeHtml'], $parts);
        return implode('&nbsp;', $parts);
    }

    /**
     * Validates an email address.
     *
     * @param string $email Address to validate
     */
    public static function validateEmail(string $email): bool
    {
        $email = trim($email);
        $email = filter_var($email, FILTER_SANITIZE_EMAIL);
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Validates a password against complexity rules.
     *
     * Rules: at least 10 characters, one uppercase, one lowercase,
     * one digit, one special character.
     *
     * @param string $password Password to validate
     * @return array{0: bool, 1: string[]} [success, list of error messages]
     */
    public static function validatePassword(string $password): array
    {
        $errors = [];
        $valid  = true;

        if (strlen($password) < 10) {
            $valid    = false;
            $errors[] = 'Le mot de passe doit contenir au moins 10 caractères.';
        }
        if (!preg_match('/[A-Z]/', $password)) {
            $valid    = false;
            $errors[] = 'Le mot de passe doit contenir au moins une lettre majuscule.';
        }
        if (!preg_match('/[a-z]/', $password)) {
            $valid    = false;
            $errors[] = 'Le mot de passe doit contenir au moins une lettre minuscule.';
        }
        if (!preg_match('/\d/', $password)) {
            $valid    = false;
            $errors[] = 'Le mot de passe doit contenir au moins un chiffre.';
        }
        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            $valid    = false;
            $errors[] = 'Le mot de passe doit contenir au moins un caractère spécial.';
        }

        return [$valid, $errors];
    }

    /**
     * Returns the list of required fields that are missing or empty.
     *
     * @param array    $data   Submitted data (e.g. $_POST)
     * @param string[] $fields Field names to check
     * @return string[] Error messages for missing fields
     */
    public static function validateRequired(array $data, array $fields): array
    {
        $errors = [];

        foreach ($fields as $field) {
            if (!array_key_exists($field, $data) || trim($data[$field]) === '') {
                $errors[] = "Le champ {$field} est requis.";
            }
        }

        return $errors;
    }

    /**
     * Generates a secure random token in hexadecimal form.
     *
     * @param int $length Number of random bytes (produces 2× hex characters)
     * @return string Hexadecimal token
     */
    public static function generateToken(int $length = 32): string
    {
        return bin2hex(random_bytes($length));
    }

    /**
     * Hashes a token with SHA-256 for safe storage in the database.
     *
     * @param string $token Plain-text token
     * @return string SHA-256 hash
     */
    public static function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }

    /**
     * Escapes a string for safe HTML output.
     *
     * @param string $data Raw string
     * @return string Escaped string
     */
    public static function escapeHtml(?string $data): string
    {
        return htmlspecialchars($data ?? '', ENT_QUOTES, 'UTF-8');
    }

    /**
     * Asserts the user is logged in.
     * Redirects to /login if not authenticated.
     *
     * @return array Current user data
     */
    public static function requireAuth(): array
    {
        if (!Session::isAuthenticated()) {
            Session::setFlash(FlashMessage::AUTH_REQUIRED, 'error');
            header('Location: /login');
            exit;
        }

        $user = Session::getUser();

        if ($user === null) {
            Session::destroy();
            Session::setFlash(FlashMessage::SESSION_EXPIRED, 'error');
            header('Location: /login');
            exit;
        }

        return $user;
    }

    /**
     * Asserts the user has the employee or admin role.
     * Redirects to /home if the role is insufficient.
     *
     * @return array Current user data
     */
    public static function requireEmploye(): array
    {
        $user = self::requireAuth();

        if ($user['role'] === 'user') {
            Session::setFlash(FlashMessage::ACCESS_DENIED, 'error');
            header('Location: /home');
            exit;
        }

        return $user;
    }

    /**
     * Asserts the user has the admin role.
     * Redirects to /home if the role is insufficient.
     *
     * @return array Current user data
     */
    public static function requireAdmin(): array
    {
        $user = self::requireAuth();

        if ($user['role'] !== 'admin') {
            Session::setFlash(FlashMessage::ADMIN_REQUIRED, 'error');
            header('Location: /home');
            exit;
        }

        return $user;
    }

    /**
 * Valider une URL de redirection interne
 *
 * @param string $redirect URL de redirection
 * @return string|null URL validée ou null si invalide
 */
    public static function validateRedirect(string $redirect): ?string
    {
        $valid = true;

        if (empty($redirect)) {
            $valid = false;
        }

        $redirect = trim($redirect);

        if (
            str_starts_with($redirect, 'http://') ||
            str_starts_with($redirect, 'https://') ||
            str_starts_with($redirect, '//')
        ) {
            $valid = false;
        }

        if (preg_match('/^(javascript|data|vbscript|file):/i', $redirect)) {
            $valid = false;
        }

        if (!str_starts_with($redirect, '/')) {
            $redirect = '/' . $redirect;
        }

        $redirect = '/' . preg_replace('#/+#', '/', ltrim($redirect, '/'));

        if (!preg_match('#^/[a-zA-Z0-9/_-]*$#', $redirect)) {
            $valid = false;
        }

        if (!$valid) {
            return null;
        }

        return $redirect;
    }
}
