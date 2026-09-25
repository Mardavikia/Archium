<?php
declare(strict_types=1);

namespace Archium\Repositories;

use PDO;

final class UserAdminRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function all(): array
    {
        $stmt = $this->pdo->query(
            'SELECT
                u.id,
                u.name,
                u.email,
                u.status,
                u.email_verified_at,
                u.last_login_at,
                u.created_at,
                u.role_id,
                r.name AS role_name,
                r.label AS role_label
             FROM users u
             LEFT JOIN roles r ON r.id = u.role_id
             WHERE u.deleted_at IS NULL
             ORDER BY u.created_at DESC'
        );

        return $stmt->fetchAll() ?: [];
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT
                u.*,
                r.name AS role_name,
                r.label AS role_label
             FROM users u
             LEFT JOIN roles r ON r.id = u.role_id
             WHERE u.id = :id
               AND u.deleted_at IS NULL
             LIMIT 1'
        );

        $stmt->execute(['id' => $id]);

        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, email
             FROM users
             WHERE email = :email
             LIMIT 1'
        );

        $stmt->execute(['email' => $email]);

        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function create(
        string $name,
        string $email,
        string $passwordHash,
        int $roleId,
        string $status
    ): int {
        $stmt = $this->pdo->prepare(
            'INSERT INTO users (
                role_id,
                name,
                email,
                password_hash,
                status,
                email_verified_at
             )
             VALUES (
                :role_id,
                :name,
                :email,
                :password_hash,
                :status,
                NOW()
             )'
        );

        $stmt->execute([
            'role_id' => $roleId,
            'name' => $name,
            'email' => $email,
            'password_hash' => $passwordHash,
            'status' => $status,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function updateRole(int $userId, int $roleId): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE users
             SET role_id = :role_id
             WHERE id = :id
               AND deleted_at IS NULL'
        );

        $stmt->execute([
            'role_id' => $roleId,
            'id' => $userId,
        ]);
    }

    public function updateStatus(int $userId, string $status): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE users
             SET status = :status
             WHERE id = :id
               AND deleted_at IS NULL'
        );

        $stmt->execute([
            'status' => $status,
            'id' => $userId,
        ]);
    }

    public function roles(): array
    {
        $stmt = $this->pdo->query(
            'SELECT id, name, label
             FROM roles
             ORDER BY id'
        );

        return $stmt->fetchAll() ?: [];
    }

    public function countActiveAdmins(): int
    {
        $stmt = $this->pdo->query(
            'SELECT COUNT(*)
             FROM users u
             INNER JOIN roles r ON r.id = u.role_id
             WHERE r.name = "admin"
               AND u.status = "active"
               AND u.deleted_at IS NULL'
        );

        return (int) $stmt->fetchColumn();
    }
}