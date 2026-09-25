<?php
declare(strict_types=1);

namespace Archium\Repositories;

use PDO;

final class UserRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE email = :e AND deleted_at IS NULL LIMIT 1');
        $stmt->execute(['e' => $email]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findActiveWithRole(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT u.*, r.name AS role_name, r.label AS role_label
             FROM users u
             LEFT JOIN roles r ON r.id = u.role_id
             WHERE u.id = :id AND u.deleted_at IS NULL AND u.status = "active"
             LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function emailExists(string $email): bool
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM users WHERE email = :e');
        $stmt->execute(['e' => $email]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function create(string $name, string $email, string $hash, int $roleId, string $status, bool $verified = false): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO users (role_id, name, email, password_hash, status, email_verified_at)
             VALUES (:r, :n, :e, :h, :s, ' . ($verified ? 'NOW()' : 'NULL') . ')'
        );
        $stmt->execute(['r' => $roleId, 'n' => $name, 'e' => $email, 'h' => $hash, 's' => $status]);
        return (int) $this->pdo->lastInsertId();
    }

    public function roleId(string $name): ?int
    {
        $stmt = $this->pdo->prepare('SELECT id FROM roles WHERE name = :n LIMIT 1');
        $stmt->execute(['n' => $name]);
        $id = $stmt->fetchColumn();
        return $id === false ? null : (int) $id;
    }

    public function markVerified(int $id): void
    {
        $this->pdo->prepare('UPDATE users SET status = "active", email_verified_at = NOW() WHERE id = :id')
            ->execute(['id' => $id]);
    }

    public function updatePassword(int $id, string $hash): void
    {
        $this->pdo->prepare('UPDATE users SET password_hash = :h WHERE id = :id')
            ->execute(['h' => $hash, 'id' => $id]);
    }

    public function touchLastLogin(int $id): void
    {
        $this->pdo->prepare('UPDATE users SET last_login_at = NOW() WHERE id = :id')->execute(['id' => $id]);
    }

    public function hasAdmin(): bool
    {
        $stmt = $this->pdo->query(
            'SELECT COUNT(*) FROM users u
             JOIN roles r ON r.id = u.role_id
             WHERE r.name = "admin" AND u.deleted_at IS NULL AND u.status = "active"'
        );
        return (int) $stmt->fetchColumn() > 0;
    }

    public function countAll(): int
    {
        return (int) $this->pdo->query('SELECT COUNT(*) FROM users WHERE deleted_at IS NULL')->fetchColumn();
    }
}