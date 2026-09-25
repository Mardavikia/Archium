<?php
declare(strict_types=1);

namespace Archium\Repositories;

use PDO;

final class WorkspaceMemberRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function forWorkspace(int $workspaceId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT wm.*, u.name, u.email, u.status, u.deleted_at, r.name AS global_role
             FROM workspace_members wm
             INNER JOIN users u ON u.id = wm.user_id
             LEFT JOIN roles r ON r.id = u.role_id
             WHERE wm.workspace_id = :workspace_id
             ORDER BY FIELD(wm.role, "owner", "editor", "viewer"), u.name'
        );
        $stmt->execute(['workspace_id' => $workspaceId]);
        return $stmt->fetchAll() ?: [];
    }

    public function find(int $workspaceId, int $memberId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT wm.*, u.name, u.email, u.status
             FROM workspace_members wm
             INNER JOIN users u ON u.id = wm.user_id
             WHERE wm.workspace_id = :workspace_id AND wm.id = :id
             LIMIT 1'
        );
        $stmt->execute(['workspace_id' => $workspaceId, 'id' => $memberId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findByUser(int $workspaceId, int $userId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM workspace_members WHERE workspace_id = :workspace_id AND user_id = :user_id LIMIT 1'
        );
        $stmt->execute(['workspace_id' => $workspaceId, 'user_id' => $userId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function add(int $workspaceId, int $userId, string $role): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO workspace_members (workspace_id, user_id, role)
             VALUES (:workspace_id, :user_id, :role)
             ON DUPLICATE KEY UPDATE role = VALUES(role), updated_at = CURRENT_TIMESTAMP'
        );
        $stmt->execute([
            'workspace_id' => $workspaceId,
            'user_id' => $userId,
            'role' => $role,
        ]);
    }

    public function updateRole(int $workspaceId, int $memberId, string $role): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE workspace_members SET role = :role WHERE workspace_id = :workspace_id AND id = :id'
        );
        $stmt->execute([
            'workspace_id' => $workspaceId,
            'id' => $memberId,
            'role' => $role,
        ]);
    }

    public function remove(int $workspaceId, int $memberId): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM workspace_members WHERE workspace_id = :workspace_id AND id = :id');
        $stmt->execute(['workspace_id' => $workspaceId, 'id' => $memberId]);
    }

    public function countOwners(int $workspaceId): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM workspace_members WHERE workspace_id = :workspace_id AND role = "owner"'
        );
        $stmt->execute(['workspace_id' => $workspaceId]);
        return (int) $stmt->fetchColumn();
    }
}