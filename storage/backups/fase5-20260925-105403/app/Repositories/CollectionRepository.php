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

    public function trashedForWorkspace(int $wsId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, name, slug, deleted_at FROM collections
             WHERE workspace_id = :w AND deleted_at IS NOT NULL
             ORDER BY deleted_at DESC'
        );
        $stmt->execute(['w' => $wsId]);
        return $stmt->fetchAll() ?: [];
    }

    public function findTrashedInWorkspace(int $id, int $wsId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM collections WHERE id = :id AND workspace_id = :w AND deleted_at IS NOT NULL LIMIT 1'
        );
        $stmt->execute(['id' => $id, 'w' => $wsId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function restore(int $id): void
    {
        $this->pdo->prepare('UPDATE collections SET deleted_at = NULL WHERE id = :id')->execute(['id' => $id]);
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
        $this->pdo->prepare('UPDATE documents SET collection_id = NULL WHERE collection_id = :id')
            ->execute(['id' => $id]);
        $this->pdo->prepare('UPDATE collections SET parent_id = NULL WHERE parent_id = :id')
            ->execute(['id' => $id]);
        $this->pdo->prepare('UPDATE collections SET deleted_at = NOW() WHERE id = :id')
            ->execute(['id' => $id]);
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
}