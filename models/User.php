<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\DatabaseConnection;
use App\Core\Security;
use PDO;

class User
{
    private static PDO $db;

    private static function getDb(): PDO
    {
        return DatabaseConnection::getInstance();
    }

    public function findById(string $id): ?array
    {
        self::$db = self::getDb();

        $query = "SELECT id, nom, prenom, email, gsm, adresse, role, created_at FROM users WHERE id = ?";
        $stmt = self::$db->prepare($query);
        $stmt->execute([$id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ? $result : null;
    }

    public function findByEmail(string $email): ?array
    {
        self::$db = self::getDb();

        $query = "SELECT * FROM users WHERE email = ?";
        $stmt = self::$db->prepare($query);
        $stmt->execute([$email]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ? $result : null;
    }

    public function emailExists(string $email): bool
    {
        self::$db = self::getDb();

        $query = "SELECT COUNT(*) FROM users WHERE email = ?";
        $stmt = self::$db->prepare($query);
        $stmt->execute([$email]);
        $result = $stmt->fetchColumn();

        if ($result > 0) {
            return true;
        }

        return false;
    }

    public function create(array $data): bool
    {
        self::$db = self::getDb();

        $query = "INSERT INTO users (nom, prenom, email, gsm, adresse, password) VALUES (:nom,:prenom,:email,:gsm,:adresse, :password)";
        $stmt = self::$db->prepare($query);
        $stmt->execute([
            'nom' => $data['nom'],
            'prenom' => $data['prenom'],
            'email' => $data['email'],
            'gsm' => $data['gsm'],
            'adresse' => $data['adresse'],
            'password' => $data['password']
        ]);

        return $stmt->rowCount() === 1;
    }

    public function update(string $id, array $data): bool
    {
        self::$db = self::getDb();

        $query = "UPDATE users SET nom = :nom, prenom = :prenom, gsm = :gsm, adresse = :adresse WHERE id = :id";
        $stmt = self::$db->prepare($query);
        $stmt->execute([
            'nom' => $data['nom'],
            'prenom' => $data['prenom'],
            'gsm' => $data['gsm'],
            'adresse' => $data['adresse'],
            'id' => $id
        ]);

        return $stmt->rowCount() === 1;
    }

    public function updatePassword(string $id, string $newPassword): bool
    {
        self::$db = self::getDb();

        $query = "UPDATE users SET password = :password WHERE id = :id";
        $stmt = self::$db->prepare($query);
        $stmt->execute([
            'password' => $newPassword,
            'id' => $id
        ]);

        return $stmt->rowCount() === 1;
    }

    public function createPasswordResetToken(string $email, string $tokenHash, string $expiresAt): bool
    {
        self::$db = self::getDb();

        $user = $this->findByEmail($email);
        $query = "INSERT INTO password_reset_tokens (user_id, token_hash, expires_at) VALUES (:user_id, :token_hash, :expires_at)";
        $stmt = self::$db->prepare($query);
        $stmt->execute([
            'user_id' => $user['id'],
            'token_hash' => $tokenHash,
            'expires_at' => $expiresAt
        ]);

        return $stmt->rowCount() === 1;
    }

    public function findPasswordResetToken(string $token): ?array
    {
        self::$db = self::getDb();

        $tokenHash = Security::hashToken($token);
        $query = "SELECT prt.*, u.email, u.id as user_id
              FROM password_reset_tokens prt
              JOIN users u ON prt.user_id = u.id
              WHERE prt.token_hash = :token_hash";
        $stmt = self::$db->prepare($query);
        $stmt->execute(['token_hash' => $tokenHash]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result === false) {
            return null;
        }

        if (strtotime($result['expires_at']) > time() && !$result['used']) {
            return $result ? $result : null;
        }

        return null;
    }

    public function markPasswordResetTokenAsUsed(string $tokenHash): bool
    {
        self::$db = self::getDb();

        $query = "UPDATE password_reset_tokens
              SET used = 1
              WHERE token_hash = :token_hash";

        $stmt = self::$db->prepare($query);
        $stmt->execute(['token_hash' => $tokenHash]);

        return $stmt->rowCount() === 1;
    }
}
