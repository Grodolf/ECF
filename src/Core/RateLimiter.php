<?php

declare(strict_types=1);

namespace App\Core;

use App\Core\DatabaseConnection;
use PDO;

/**
 * IP-based rate limiter backed by the rate_limits database table.
 *
 * Limits per action:
 *   login          : 5 attempts / 15 min window → block 15 min  (identifier: sha256(IP:email))
 *   reset_password : 3 attempts / 1 h window    → block 1 h     (identifier: sha256(IP))
 *   change_password: 5 attempts / 15 min window → block 15 min  (identifier: sha256(IP))
 */
class RateLimiter
{
    private const LIMITS = [
        'login'           => ['max' => 5, 'window' => 900,  'block' => 900],
        'reset_password'  => ['max' => 3, 'window' => 3600, 'block' => 3600],
        'change_password' => ['max' => 5, 'window' => 900,  'block' => 900],
    ];

    private static function getDb(): PDO
    {
        return DatabaseConnection::getInstance();
    }

    /**
     * Returns true if the identifier is allowed to proceed, false if blocked.
     */
    public static function check(string $action, string $identifier): bool
    {
        $config = self::LIMITS[$action];
        $row    = self::fetchRow($action, $identifier);

        if ($row === null) {
            return true;
        }

        if ($row['blocked_until'] !== null && strtotime($row['blocked_until']) > time()) {
            return false;
        }

        if (strtotime($row['first_attempt_at']) + $config['window'] < time()) {
            return true;
        }

        return $row['attempts'] < $config['max'];
    }

    /**
     * Records a failed attempt. Sets blocked_until when the threshold is reached.
     */
    public static function hit(string $action, string $identifier): void
    {
        $config = self::LIMITS[$action];
        $db     = self::getDb();
        $row    = self::fetchRow($action, $identifier);

        if ($row === null) {
            $db->prepare("
                INSERT INTO rate_limits (action, identifier, attempts, first_attempt_at)
                VALUES (?, ?, 1, NOW())
            ")->execute([$action, $identifier]);
            return;
        }

        if (strtotime($row['first_attempt_at']) + $config['window'] < time()) {
            $db->prepare("
                UPDATE rate_limits
                SET attempts = 1, first_attempt_at = NOW(), blocked_until = NULL
                WHERE id = ?
            ")->execute([$row['id']]);
            return;
        }

        $newAttempts  = $row['attempts'] + 1;
        $blockedUntil = $newAttempts >= $config['max']
            ? date('Y-m-d H:i:s', time() + $config['block'])
            : null;

        $db->prepare("
            UPDATE rate_limits SET attempts = ?, blocked_until = ? WHERE id = ?
        ")->execute([$newAttempts, $blockedUntil, $row['id']]);
    }

    /**
     * Deletes the rate limit record on successful authentication.
     */
    public static function reset(string $action, string $identifier): void
    {
        self::getDb()->prepare("
            DELETE FROM rate_limits WHERE action = ? AND identifier = ?
        ")->execute([$action, $identifier]);
    }

    /**
     * Builds a hashed identifier for login (IP + email) to target a specific account.
     */
    public static function loginIdentifier(string $ip, string $email): string
    {
        return hash('sha256', $ip . ':' . strtolower(trim($email)));
    }

    /**
     * Builds a hashed identifier for IP-only actions (reset, change-password).
     */
    public static function ipIdentifier(string $ip): string
    {
        return hash('sha256', $ip);
    }

    private static function fetchRow(string $action, string $identifier): ?array
    {
        $stmt = self::getDb()->prepare("
            SELECT id, attempts, first_attempt_at, blocked_until
            FROM rate_limits
            WHERE action = ? AND identifier = ?
        ");
        $stmt->execute([$action, $identifier]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }
}
