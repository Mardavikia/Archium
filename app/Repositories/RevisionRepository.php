<?php
declare(strict_types=1);

namespace Archium\Repositories;

use PDO;

final class RevisionRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    /** Salva uno snapshot dello stato (titolo + markdown) come nuova revisione. */
    public function add(int $documentId, string $title, ?string $markdown, int $userId): void
    {
        $stmt = $this->pdo->prepare(
            'SELECT COALESCE(MAX(revision_number), 0) + 1 FROM document_revisions WHERE document_id = :d'
        );
        $stmt->execute(['d' => $documentId]);
        $next = (int) $stmt->fetchColumn();

        $this->pdo->prepare(
            'INSERT INTO document_revisions (document_id, revision_number, title, content_markdown, created_by)
             VALUES (:d, :n, :t, :m, :u)'
        )->execute(['d' => $documentId, 'n' => $next, 't' => $title, 'm' => $markdown, 'u' => $userId]);
    }

    public function forDocument(int $documentId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT r.*, u.name AS author_name
             FROM document_revisions r LEFT JOIN users u ON u.id = r.created_by
             WHERE r.document_id = :d
             ORDER BY r.revision_number DESC'
        );
        $stmt->execute(['d' => $documentId]);
        return $stmt->fetchAll() ?: [];
    }

    public function findByNumber(int $documentId, int $number): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT r.*, u.name AS author_name
             FROM document_revisions r LEFT JOIN users u ON u.id = r.created_by
             WHERE r.document_id = :d AND r.revision_number = :n
             LIMIT 1'
        );
        $stmt->execute(['d' => $documentId, 'n' => $number]);
        $row = $stmt->fetch();
        return $row ?: null;
    }
}