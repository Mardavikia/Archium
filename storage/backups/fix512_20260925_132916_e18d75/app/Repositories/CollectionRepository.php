<?php
declare(strict_types=1);

namespace Archium\Repositories;

use PDO;

final class CollectionRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function forWorkspace(int $wsId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT c.*, p.name AS parent_name,
                    (SELECT COUNT(*) FROM documents d WHERE d.collection_id = c.id AND d.deleted_at IS NULL) AS doc_count
             FROM collections c
             LEFT JOIN collections p ON p.id = c.parent_id AND p.deleted_at IS NULL
             WHERE c.workspace_id = :w AND c.deleted_at IS NULL
             ORDER BY c.name'
        );
        $stmt->execute(['w' => $wsId]);
        return $stmt->fetchAll() ?: [];
    }

    public function findInWorkspace(int $id, int $wsId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM collections WHERE id = :id AND workspace_id = :w AND deleted_at IS NULL LIMIT 1'
        );
        $stmt->execute(['id' => $id, 'w' => $wsId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function slugTaken(int $wsId, string $slug, ?int $excludeId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM collections WHERE workspace_id = :w AND slug = :s AND deleted_at IS NULL';
        $params = ['w' => $wsId, 's' => $slug];
        if ($excludeId !== null) {
            $sql .= ' AND id <> :x';
            $params['x'] = $excludeId;
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function create(int $wsId, ?int $parentId, string $name, string $slug, ?string $description): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO collections (workspace_id, parent_id, name, slug, description)
             VALUES (:w, :p, :n, :s, :d)'
        );
        $stmt->execute(['w' => $wsId, 'p' => $parentId, 'n' => $name, 's' => $slug, 'd' => $description]);
        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, ?int $parentId, string $name, string $slug, ?string $description): void
    {
        $this->pdo->prepare(
            'UPDATE collections SET parent_id = :p, name = :n, slug = :s, description = :d WHERE id = :id'
        )->execute(['p' => $parentId, 'n' => $name, 's' => $slug, 'd' => $description, 'id' => $id]);
    }

    public function softDelete(int $id): void
    {
        $this->pdo->prepare('UPDATE documents SET collection_id = NULL WHERE collection_id = :id')->execute(['id' => $id]);
        $this->pdo->prepare('UPDATE collections SET parent_id = NULL WHERE parent_id = :id')->execute(['id' => $id]);
        $this->pdo->prepare('UPDATE collections SET deleted_at = NOW() WHERE id = :id')->execute(['id' => $id]);
    }

    public function isSelfOrDescendant(int $collectionId, int $candidateParentId): bool
    {
        if ($collectionId === $candidateParentId) {
            return true;
        }
        $current = $candidateParentId;
        for ($i = 0; $i < 50; $i++) {
            $stmt = $this->pdo->prepare('SELECT parent_id FROM collections WHERE id = :id LIMIT 1');
            $stmt->execute(['id' => $current]);
            $parent = $stmt->fetchColumn();
            if ($parent === false || $parent === null) {
                return false;
            }
            if ((int) $parent === $collectionId) {
                return true;
            }
            $current = (int) $parent;
        }
        return true;
    }

    public function memberPermission(int $collectionId, int $userId): ?string
    {
        $stmt = $this->pdo->prepare(
            'SELECT permission FROM collection_members WHERE collection_id = :collection_id AND user_id = :user_id LIMIT 1'
        );
        $stmt->execute(['collection_id' => $collectionId, 'user_id' => $userId]);
        $permission = $stmt->fetchColumn();
        return $permission === false ? null : (string) $permission;
    }

    public function permissions(int $collectionId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT cm.*, u.name, u.email
             FROM collection_members cm
             INNER JOIN users u ON u.id = cm.user_id
             WHERE cm.collection_id = :collection_id
             ORDER BY u.name'
        );
        $stmt->execute(['collection_id' => $collectionId]);
        return $stmt->fetchAll() ?: [];
    }

    public function grantPermission(int $collectionId, int $userId, string $permission): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO collection_members (collection_id, user_id, permission)
             VALUES (:collection_id, :user_id, :permission)
             ON DUPLICATE KEY UPDATE permission = VALUES(permission), updated_at = CURRENT_TIMESTAMP'
        );
        $stmt->execute(['collection_id' => $collectionId, 'user_id' => $userId, 'permission' => $permission]);
    }

    public function revokePermission(int $collectionId, int $userId): void
    {
        $stmt = $this->pdo->prepare(
            'DELETE FROM collection_members WHERE collection_id = :collection_id AND user_id = :user_id'
        );
        $stmt->execute(['collection_id' => $collectionId, 'user_id' => $userId]);
    }
}