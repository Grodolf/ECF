<?php

declare(strict_types=1);

namespace App\Core;

use App\Core\Session;

class Security
{
    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT);
    }

    public static function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    public static function generateCsrfToken(): string
    {
        $token = bin2hex(random_bytes(32));
        Session::set('csrf_token', $token);
        return $token;
    }

    public static function verifyCsrfToken(string $token): bool
    {
        $session_token = Session::get('csrf_token');
        return hash_equals($session_token, $token);
    }

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

    public static function validateEmail(string $email): bool
    {
        $email = trim($email);
        $email = filter_var($email, FILTER_SANITIZE_EMAIL);
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    public static function validatePassword(string $password): array
    {
        $errors = [];
        $valid = true;

        if (strlen($password) < 10) {
            $valid = false;
            array_push($errors, 'Le mot de passe doit contenir au moins 10 caractères');
        }
        if (!preg_match('/[A-Z]/', $password)) {
            $valid = false;
            array_push($errors, 'Le mot de passe doit contenir au moins une lettre majuscule');
        }
        if (!preg_match('/[a-z]/', $password)) {
            $valid = false;
            array_push($errors, 'Le mot de passe doit contenir au moins une lettre minuscule');
        }
        if (!preg_match('/\d/', $password)) {
            $valid = false;
            array_push($errors, 'Le mot de passe doit contenir au moins un chiffre');
        }
        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            $valid = false;
            array_push($errors, 'Le mot de passe doit contenir au moins un caractère spécial');
        }
        return [$valid,$errors];
    }

    public static function validateRequired(array $data, array $fields): array
    {
        $errors = [];

        foreach ($fields as $field) {
            if (!array_key_exists($field, $data) || trim($data[$field]) === '') {
                array_push($errors, "Le champ {$field} est requis.");
            }
        }
        return $errors;
    }

    public static function generateToken(int $length = 32): string
    {
        $token = random_bytes($length);
        $token = bin2hex($token);
        return $token;
    }

    public static function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }

    public static function escapeHtml(string $data): string
    {
        return htmlspecialchars($data);
    }

    /**
     * TODO
     *
     * public static function checkRateLimit(string $key, int $maxAttempts = 5, int $decay = 300): bool {}
     */
}
