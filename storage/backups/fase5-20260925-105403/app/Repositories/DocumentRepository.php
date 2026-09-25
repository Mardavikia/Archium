<?php
declare(strict_types=1);

namespace Archium\Repositories;

use Archium\Support\Str;
use PDO;

final class DocumentRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function forWorkspace(int $wsId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT d.id, d.title, d.slug, d.collection_id, d.parent_id, d.updated_at,
                    c.name AS collection_name, p.title AS parent_title
             FROM documents d
             LEFT JOIN collections c ON c.id = d.collection_id AND c.deleted_at IS NULL
             LEFT JOIN documents p ON p.id = d.parent_id AND p.deleted_at IS NULL
             WHERE d.workspace_id = :w AND d.deleted_at IS NULL
             ORDER BY d.title'
        );
        $stmt->execute(['w' => $wsId]);
        return $stmt->fetchAll() ?: [];
    }

    public function findInWorkspace(int $id, int $wsId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM documents WHERE id = :id AND workspace_id = :w AND deleted_at IS NULL LIMIT 1'
        );
        $stmt->execute(['id' => $id, 'w' => $wsId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findTrashedInWorkspace(int $id, int $wsId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM documents WHERE id = :id AND workspace_id = :w AND deleted_at IS NOT NULL LIMIT 1'
        );
        $stmt->execute(['id' => $id, 'w' => $wsId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function trashedForWorkspace(int $wsId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, title, slug, deleted_at FROM documents
             WHERE workspace_id = :w AND deleted_at IS NOT NULL
             ORDER BY deleted_at DESC'
        );
        $stmt->execute(['w' => $wsId]);
        return $stmt->fetchAll() ?: [];
    }

    public function restore(int $id): void
    {
        $this->pdo->prepare('UPDATE documents SET deleted_at = NULL WHERE id = :id')->execute(['id' => $id]);
    }

    /** Eliminazione definitiva: revisioni/tag/preferiti/link pubblici cadono via FK cascade. */
    public function forceDelete(int $id): void
    {
        $this->pdo->prepare('DELETE FROM documents WHERE id = :id')->execute(['id' => $id]);
    }

    public function slugTaken(int $wsId, string $slug, ?int $excludeId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM documents WHERE workspace_id = :w AND slug = :s AND deleted_at IS NULL';
        $params = ['w' => $wsId, 's' => $slug];
        if ($excludeId !== null) {
            $sql .= ' AND id <> :x';
            $params['x'] = $excludeId;
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function uniqueSlug(int $wsId, string $base, ?int $excludeId = null): string
    {
        $slug = Str::slug($base);
        $candidate = $slug;
        $i = 2;
        while ($this->slugTaken($wsId, $candidate, $excludeId)) {
            $candidate = $slug . '-' . $i++;
        }
        return $candidate;
    }

    public function create(int $wsId, ?int $collectionId, ?int $parentId, string $title, string $slug, ?string $markdown, ?string $html, int $userId): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO documents (workspace_id, collection_id, parent_id, title, slug, content_markdown, content_html, created_by, updated_by)
             VALUES (:w, :c, :p, :t, :s, :m, :h, :u, :u2)'
        );
        $stmt->execute([
            'w' => $wsId, 'c' => $collectionId, 'p' => $parentId,
            't' => $title, 's' => $slug, 'm' => $markdown, 'h' => $html, 'u' => $userId, 'u2' => $userId,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, ?int $collectionId, ?int $parentId, string $title, ?string $markdown, ?string $html, int $userId): void
    {
        $this->pdo->prepare(
            'UPDATE documents
             SET collection_id = :c, parent_id = :p, title = :t, content_markdown = :m, content_html = :h, updated_by = :u
             WHERE id = :id'
        )->execute([
            'c' => $collectionId, 'p' => $parentId, 't' => $title, 'm' => $markdown, 'h' => $html, 'u' => $userId, 'id' => $id,
        ]);
    }

    public function softDelete(int $id): void
    {
        $this->pdo->prepare('UPDATE attachments SET document_id = NULL WHERE document_id = :id')
            ->execute(['id' => $id]);
        $this->pdo->prepare('UPDATE documents SET parent_id = NULL WHERE parent_id = :id')
            ->execute(['id' => $id]);
        $this->pdo->prepare('UPDATE documents SET deleted_at = NOW() WHERE id = :id')
            ->execute(['id' => $id]);
    }

    public function isSelfOrDescendant(int $docId, int $candidateParentId): bool
    {
        if ($docId === $candidateParentId) {
            return true;
        }
        $current = $candidateParentId;
        for ($i = 0; $i < 50; $i++) {
            $stmt = $this->pdo->prepare('SELECT parent_id FROM documents WHERE id = :id LIMIT 1');
            $stmt->execute(['id' => $current]);
            $parent = $stmt->fetchColumn();
            if ($parent === false || $parent === null) {
                return false;
            }
            if ((int) $parent === $docId) {
                return true;
            }
            $current = (int) $parent;
        }
        return true;
    }

    public function countForWorkspace(int $wsId): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM documents WHERE workspace_id = :w AND deleted_at IS NULL');
        $stmt->execute(['w' => $wsId]);
        return (int) $stmt->fetchColumn();
    }
}