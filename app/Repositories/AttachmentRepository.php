<?php
declare(strict_types=1);

namespace Archium\Repositories;

use PDO;

final class AttachmentRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function forDocument(int $docId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT a.*, u.name AS uploader_name
             FROM attachments a LEFT JOIN users u ON u.id = a.uploaded_by
             WHERE a.document_id = :d AND a.deleted_at IS NULL
             ORDER BY a.created_at'
        );
        $stmt->execute(['d' => $docId]);
        return $stmt->fetchAll() ?: [];
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM attachments WHERE id = :id AND deleted_at IS NULL LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function softDelete(int $id): void
    {
        $this->pdo->prepare('UPDATE attachments SET deleted_at = NOW() WHERE id = :id')->execute(['id' => $id]);
    }
}