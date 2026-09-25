<?php
declare(strict_types=1);

namespace Archium\Repositories;

use PDO;

final class FavoriteRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    /** Toggle: restituisce true se il documento e' stato AGGIUNTO ai preferiti. */
    public function toggle(int $userId, int $docId): bool
    {
        $stmt = $this->pdo->prepare('SELECT id FROM favorites WHERE user_id = :u AND document_id = :d LIMIT 1');
        $stmt->execute(['u' => $userId, 'd' => $docId]);
        $id = $stmt->fetchColumn();

        if ($id !== false) {
            $this->pdo->prepare('DELETE FROM favorites WHERE id = :id')->execute(['id' => (int) $id]);
            return false;
        }
        $this->pdo->prepare('INSERT INTO favorites (user_id, document_id) VALUES (:u, :d)')
            ->execute(['u' => $userId, 'd' => $docId]);
        return true;
    }

    public function isFavorite(int $userId, int $docId): bool
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM favorites WHERE user_id = :u AND document_id = :d');
        $stmt->execute(['u' => $userId, 'd' => $docId]);
        return (int) $stmt->fetchColumn() > 0;
    }

    /** Preferiti dell'utente, filtrati di nuovo sulla membership corrente (difesa in profondita'). */
    public function forUser(int $userId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT d.id, d.title, d.slug, d.updated_at, w.name AS workspace_name, f.created_at AS fav_at
             FROM favorites f
             JOIN documents d ON d.id = f.document_id AND d.deleted_at IS NULL
             JOIN workspaces w ON w.id = d.workspace_id AND w.deleted_at IS NULL
             WHERE f.user_id = :u
               AND (w.owner_id = :u2 OR EXISTS (
                    SELECT 1 FROM workspace_members m
                    WHERE m.workspace_id = d.workspace_id AND m.user_id = :u3))
             ORDER BY f.created_at DESC'
        );
        $stmt->execute(['u' => $userId, 'u2' => $userId, 'u3' => $userId]);
        return $stmt->fetchAll() ?: [];
    }
}