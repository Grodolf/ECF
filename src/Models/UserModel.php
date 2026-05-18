<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\DatabaseConnection;
use App\Core\Security;
use PDO;

/**
 * Data-access layer for the users table and the password_reset_tokens table.
 */
class UserModel
{
    /**
     * Returns the shared PDO instance.
     */
    private static function getDb(): PDO
    {
        return DatabaseConnection::getInstance();
    }

    /**
     * Finds a user by primary key, excluding the password hash.
     *
     * @param string $id User UUID.
     * @return array|null User row without the password column, or null if not found.
     */
    public function findById(string $id): ?array
    {
        $query = "SELECT id, nom, prenom, email, gsm, adresse, role, created_at FROM users WHERE id = ?";
        $stmt = self::getDb()->prepare($query);
        $stmt->execute([$id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ?: null;
    }


    /**
     * Finds a user by e-mail address, including the password hash.
     *
     * Intended for authentication only — do not expose the result to views.
     *
     * @param string $email E-mail address to look up.
     * @return array|null Full user row (including password), or null if not found.
     */
    public function findByEmail(string $email): ?array
    {
        $query = "SELECT * FROM users WHERE email = ?";
        $stmt = self::getDb()->prepare($query);
        $stmt->execute([$email]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    /**
     * Finds a user by e-mail address, returning only non-sensitive fields (id, nom, prenom).
     *
     * Used when a name is needed for an e-mail template without exposing credentials.
     *
     * @param string $email E-mail address to look up.
     * @return array|null Partial user row, or null if not found.
     */
    public function findByEmailPublic(string $email): ?array
    {
        $query = "SELECT id, nom, prenom FROM users WHERE email = ?";
        $stmt = self::getDb()->prepare($query);
        $stmt->execute([$email]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    /**
     * Returns true if the given e-mail address is already registered.
     *
     * @param string $email E-mail address to check.
     */
    public function emailExists(string $email): bool
    {
        $query = "SELECT COUNT(*) FROM users WHERE email = ?";
        $stmt = self::getDb()->prepare($query);
        $stmt->execute([$email]);
        $result = $stmt->fetchColumn();

        if ($result > 0) {
            return true;
        }

        return false;
    }

    /**
     * Inserts a new user record.
     *
     * Expects $data to contain: nom, prenom, email, gsm, adresse, code_postal, city,
     * and password (already hashed).
     *
     * @param array $data Sanitised and validated user fields.
     * @return bool True if exactly one row was inserted.
     */
    public function create(array $data): bool
    {
        $query = "INSERT INTO users (nom, prenom, email, gsm, adresse, code_postal, city, password) VALUES (:nom,:prenom,:email,:gsm,:adresse,:code_postal,:city,:password)";
        $stmt = self::getDb()->prepare($query);
        $stmt->execute([
            'nom' => $data['nom'],
            'prenom' => $data['prenom'],
            'email' => $data['email'],
            'gsm' => $data['gsm'],
            'adresse' => $data['adresse'],
            'code_postal' => $data['code_postal'],
            'city' => $data['city'],
            'password' => $data['password']
        ]);

        return $stmt->rowCount() === 1;
    }

    /**
     * Updates the profile fields of an existing user (excludes password and e-mail).
     *
     * Expects $data to contain: nom, prenom, gsm, adresse, code_postal, city.
     *
     * @param int   $id   User identifier.
     * @param array $data Sanitised and validated profile fields.
     * @return bool True if exactly one row was updated.
     */
    public function update(int $id, array $data): bool
    {
        $query = "UPDATE users SET nom = :nom, prenom = :prenom, gsm = :gsm, adresse = :adresse, code_postal = :code_postal, city = :city WHERE id = :id";
        $stmt = self::getDb()->prepare($query);
        $stmt->execute([
            'nom' => $data['nom'],
            'prenom' => $data['prenom'],
            'gsm' => $data['gsm'],
            'adresse' => $data['adresse'],
            'code_postal' => $data['code_postal'],
            'city' => $data['city'],
            'id' => $id
        ]);

        return $stmt->rowCount() === 1;
    }

    /**
     * Replaces the stored password hash for a user.
     *
     * @param int    $id          User identifier.
     * @param string $newPassword Bcrypt hash of the new password.
     * @return bool True if exactly one row was updated.
     */
    public function updatePassword(int $id, string $newPassword): bool
    {
        $query = "UPDATE users SET password = :password WHERE id = :id";
        $stmt = self::getDb()->prepare($query);
        $stmt->execute([
            'password' => $newPassword,
            'id' => $id
        ]);

        return $stmt->rowCount() === 1;
    }

    /**
     * Stores a hashed password-reset token for the user matching the given e-mail.
     *
     * Resolves the user_id internally via findByEmail so the caller only needs
     * to pass the e-mail address.
     *
     * @param string $email     E-mail address of the requesting user.
     * @param string $tokenHash SHA-256 hash of the plain-text token.
     * @param string $expiresAt Expiry datetime in Y-m-d H:i:s format (typically now + 1 hour).
     * @return bool True if exactly one row was inserted.
     */
    public function createPasswordResetToken(string $email, string $tokenHash, string $expiresAt): bool
    {
        $user = $this->findByEmail($email);
        $query = "INSERT INTO password_reset_tokens (user_id, token_hash, expires_at) VALUES (:user_id, :token_hash, :expires_at)";
        $stmt = self::getDb()->prepare($query);
        $stmt->execute([
            'user_id' => $user['id'],
            'token_hash' => $tokenHash,
            'expires_at' => $expiresAt
        ]);

        return $stmt->rowCount() === 1;
    }

    /**
     * Looks up a valid (non-expired, unused) password-reset token by its plain-text value.
     *
     * Hashes the token internally before querying, then validates expiry and the
     * used flag. Returns null for unknown, expired, or already-used tokens.
     *
     * @param string $token Plain-text token from the reset link.
     * @return array|null Token row joined with the user's e-mail and id, or null if invalid.
     */
    public function findPasswordResetToken(string $token): ?array
    {
        $tokenHash = Security::hashToken($token);
        $query = "SELECT prt.*, u.email, u.id as user_id
              FROM password_reset_tokens prt
              JOIN users u ON prt.user_id = u.id
              WHERE prt.token_hash = :token_hash";
        $stmt = self::getDb()->prepare($query);
        $stmt->execute(['token_hash' => $tokenHash]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result === false) {
            return null;
        }

        if (strtotime($result['expires_at']) > time() && !$result['used']) {
            return $result ?: null;
        }

        return null;
    }

    /**
     * Marks a password-reset token as used so it cannot be replayed.
     *
     * @param string $tokenHash SHA-256 hash of the token to invalidate.
     * @return bool True if exactly one row was updated.
     */
    public function markPasswordResetTokenAsUsed(string $tokenHash): bool
    {
        $query = "UPDATE password_reset_tokens
              SET used = 1
              WHERE token_hash = :token_hash";

        $stmt = self::getDb()->prepare($query);
        $stmt->execute(['token_hash' => $tokenHash]);

        return $stmt->rowCount() === 1;
    }

    public function findAllEmployes(): array
    {
        $query = "SELECT * FROM users WHERE role = 'employe'";

        $stmt = self::getDb()->prepare($query);
        $stmt->execute();

        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $result ?: [];
    }

    public function toggleActive(string $id): bool
    {
        $query = "UPDATE users SET actif = NOT actif WHERE id = ?";

        $stmt = self::getDb()->prepare($query);
        $stmt->execute([$id]);

        return $stmt->rowCount() === 1;
    }

    public function createEmploye(array $data): bool
    {
        $query = "INSERT INTO users (nom, prenom, email, password, role)
                VALUES (:nom, :prenom, :email, :password, 'employe')";

        $stmt = self::getDb()->prepare($query);
        $stmt->execute([
            'nom' => $data['nom'],
            'prenom' => $data['prenom'],
            'email' => $data['email'],
            'password' => $data['password'],
        ]);

        return $stmt->rowCount() === 1;
    }
}
