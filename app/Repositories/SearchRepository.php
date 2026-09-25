<?php
declare(strict_types=1);

namespace Archium\Repositories;

use PDO;
use PDOException;

final class SearchRepository
{
    private const LIMIT = 50;

    public function __construct(private PDO $pdo)
    {
    }

    /**
     * Ricerca sui documenti visibili all'utente (owner o membro del workspace).
     * FULLTEXT naturale con fallback LIKE automatico.
     */
    public function search(int $userId, string $query): array
    {
        $query = trim($query);
        if (mb_strlen($query) < 2) {
            return [];
        }

        $base = 'SELECT d.id, d.title, d.slug, d.content_markdown, d.updated_at,
                        d.workspace_id, w.name AS workspace_name
                 FROM documents d
                 JOIN workspaces w ON w.id = d.workspace_id AND w.deleted_at IS NULL
                 WHERE d.deleted_at IS NULL
                   AND (w.owner_id = :u OR EXISTS (
                        SELECT 1 FROM workspace_members m
                        WHERE m.workspace_id = d.workspace_id AND m.user_id = :u2))';

        // 1) tentativo FULLTEXT
        try {
            $stmt = $this->pdo->prepare(
                $base . ' AND MATCH (d.title, d.content_markdown) AGAINST (:q IN NATURAL LANGUAGE MODE)
                          ORDER BY d.updated_at DESC LIMIT ' . self::LIMIT
            );
            $stmt->execute(['u' => $userId, 'u2' => $userId, 'q' => $query]);
            $rows = $stmt->fetchAll() ?: [];
        } catch (PDOException) {
            $rows = []; // es. tabella senza indice utilizzabile: si passa al fallback
        }

        // 2) fallback LIKE: query corte, stopword, o zero risultati FULLTEXT
        if ($rows === []) {
            $like = '%' . $this->escapeLike($query) . '%';
            $stmt = $this->pdo->prepare(
                $base . " AND (d.title LIKE :like ESCAPE '!' OR d.content_markdown LIKE :like2 ESCAPE '!')
                          ORDER BY d.updated_at DESC LIMIT " . self::LIMIT
            );
            $stmt->execute(['u' => $userId, 'u2' => $userId, 'like' => $like, 'like2' => $like]);
            $rows = $stmt->fetchAll() ?: [];
        }

        return $rows;
    }

    private function escapeLike(string $value): string
    {
        return strtr($value, ['!' => '!!', '%' => '!%', '_' => '!_']);
    }
}