<?php
declare(strict_types=1);

namespace Archium\Repositories;

use Archium\Support\Str;
use PDO;

final class WorkspaceRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    /** Workspace di cui l'utente e' owner o membro. */
    public function forUser(int $userId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT w.*, COALESCE(m.role, CASE WHEN w.owner_id = :u2 THEN "owner" END) AS my_role
             FROM workspaces w
             LEFT JOIN workspace_members m ON m.workspace_id = w.id AND m.user_id = :u
             WHERE w.deleted_at IS NULL AND (w.owner_id = :u3 OR m.user_id IS NOT NULL)
             ORDER BY w.created_at'
        );
        $stmt->execute(['u' => $userId, 'u2' => $userId, 'u3' => $userId]);
        return $stmt->fetchAll() ?: [];
    }

    public function findActive(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM workspaces WHERE id = :id AND deleted_at IS NULL LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** Ruolo effettivo dell'utente nel workspace ('owner'|'editor'|'viewer'|null). */
    public function roleOf(int $workspaceId, int $userId): ?string
    {
        $stmt = $this->pdo->prepare('SELECT owner_id FROM workspaces WHERE id = :id AND deleted_at IS NULL');
        $stmt->execute(['id' => $workspaceId]);
        $ownerId = $stmt->fetchColumn();
        if ($ownerId === false) {
            return null;
        }
        if ((int) $ownerId === $userId) {
            return 'owner';
        }
        $stmt = $this->pdo->prepare(
            'SELECT role FROM workspace_members WHERE workspace_id = :w AND user_id = :u LIMIT 1'
        );
        $stmt->execute(['w' => $workspaceId, 'u' => $userId]);
        $role = $stmt->fetchColumn();
        return $role === false ? null : (string) $role;
    }

    public function create(string $name, string $slug, int $ownerId, ?string $description): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO workspaces (owner_id, name, slug, description) VALUES (:o, :n, :s, :d)'
        );
        $stmt->execute(['o' => $ownerId, 'n' => $name, 's' => $slug, 'd' => $description]);
        $id = (int) $this->pdo->lastInsertId();

        $this->pdo->prepare(
            'INSERT INTO workspace_members (workspace_id, user_id, role) VALUES (:w, :u, "owner")
             ON DUPLICATE KEY UPDATE role = "owner"'
        )->execute(['w' => $id, 'u' => $ownerId]);

        return $id;
    }

    public function update(int $id, string $name, ?string $description): void
    {
        $this->pdo->prepare('UPDATE workspaces SET name = :n, description = :d WHERE id = :id')
            ->execute(['n' => $name, 'd' => $description, 'id' => $id]);
    }

    public function uniqueSlug(string $base): string
    {
        $slug = Str::slug($base);
        $candidate = $slug;
        $i = 2;
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM workspaces WHERE slug = :s');
        while (true) {
            $stmt->execute(['s' => $candidate]);
            if ((int) $stmt->fetchColumn() === 0) {
                return $candidate;
            }
            $candidate = $slug . '-' . $i++;
        }
    }

    /** Primo workspace dell'utente; se non esiste, crea "Workspace personale". */
    public function ensureDefaultFor(int $userId, string $userName): array
    {
        $existing = $this->forUser($userId);
        if ($existing !== []) {
            $ws = $existing[0];
            $ws['my_role'] = $ws['my_role'] ?? 'owner';
            return $ws;
        }
        $id = $this->create(
            'Workspace di ' . $userName,
            $this->uniqueSlug('workspace-' . $userName),
            $userId,
            'Workspace creato automaticamente'
        );
        $ws = $this->findActive($id);
        $ws['my_role'] = 'owner';
        return $ws;
    }
}