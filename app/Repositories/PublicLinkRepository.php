<?php
declare(strict_types=1);

namespace Archium\Repositories;

use PDO;

final class PublicLinkRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    /** Restituisce il token in chiaro una sola volta al chiamante; DB salva solo SHA-256. */
    public function create(int $documentId, ?string $expiresAt, int $createdBy): string
    {
        $token = bin2hex(random_bytes(32));
        $stmt = $this->pdo->prepare(
            'INSERT INTO public_links (document_id, token_hash, expires_at, created_by)
             VALUES (:document_id, :token_hash, :expires_at, :created_by)'
        );
        $stmt->execute([
            'document_id' => $documentId,
            'token_hash' => hash('sha256', $token),
            'expires_at' => $expiresAt,
            'created_by' => $createdBy,
        ]);
        return $token;
    }

    public function forDocument(int $documentId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT pl.*, u.name AS creator_name
             FROM public_links pl
             LEFT JOIN users u ON u.id = pl.created_by
             WHERE pl.document_id = :document_id
             ORDER BY pl.created_at DESC'
        );
        $stmt->execute(['document_id' => $documentId]);
        return $stmt->fetchAll() ?: [];
    }

    public function findForDocument(int $documentId, int $linkId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM public_links WHERE id = :id AND document_id = :document_id LIMIT 1'
        );
        $stmt->execute(['id' => $linkId, 'document_id' => $documentId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function revoke(int $linkId): void
    {
        $stmt = $this->pdo->prepare('UPDATE public_links SET revoked_at = NOW() WHERE id = :id');
        $stmt->execute(['id' => $linkId]);
    }

    public function findActiveByToken(string $token): ?array
    {
        if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
            return null;
        }

        $stmt = $this->pdo->prepare(
            'SELECT pl.*, d.id AS document_id, d.workspace_id, d.title, d.slug, d.content_html, d.content_markdown,
                    d.updated_at, w.name AS workspace_name
             FROM public_links pl
             INNER JOIN documents d ON d.id = pl.document_id
             INNER JOIN workspaces w ON w.id = d.workspace_id
             WHERE pl.token_hash = :token_hash
               AND pl.revoked_at IS NULL
               AND (pl.expires_at IS NULL OR pl.expires_at > NOW())
               AND d.deleted_at IS NULL
               AND w.deleted_at IS NULL
             LIMIT 1'
        );
        $stmt->execute(['token_hash' => hash('sha256', $token)]);
        $row = $stmt->fetch();
        return $row ?: null;
    }
}