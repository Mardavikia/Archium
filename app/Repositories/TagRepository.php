<?php
declare(strict_types=1);

namespace Archium\Repositories;

use Archium\Support\Str;
use PDO;

final class TagRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function forWorkspace(int $workspaceId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT *
             FROM tags
             WHERE workspace_id = :workspace_id
             ORDER BY name'
        );

        $stmt->execute([
            'workspace_id' => $workspaceId,
        ]);

        return $stmt->fetchAll() ?: [];
    }

    public function forDocument(int $documentId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT t.*
             FROM tags t
             INNER JOIN document_tags dt ON dt.tag_id = t.id
             WHERE dt.document_id = :document_id
             ORDER BY t.name'
        );

        $stmt->execute([
            'document_id' => $documentId,
        ]);

        return $stmt->fetchAll() ?: [];
    }

    /**
     * Converte "rete, vpn, howto" in un array di tag unici.
     *
     * Limiti:
     * - max 10 tag;
     * - max 50 caratteri ciascuno;
     * - nessun duplicato case-insensitive.
     *
     * @return string[]
     */
    public static function parse(string $input): array
    {
        $tags = [];

        foreach (explode(',', $input) as $part) {
            $name = mb_substr(trim($part), 0, 50);

            if ($name === '') {
                continue;
            }

            $key = mb_strtolower($name);

            if (isset($tags[$key])) {
                continue;
            }

            $tags[$key] = $name;

            if (count($tags) >= 10) {
                break;
            }
        }

        return array_values($tags);
    }

    /**
     * Sincronizza tutti i tag associati a un documento.
     *
     * Prima elimina le associazioni esistenti, poi crea i tag mancanti
     * nel workspace e collega quelli selezionati al documento.
     *
     * @param string[] $names
     */
    public function syncDocument(int $workspaceId, int $documentId, array $names): void
    {
        $deleteLinks = $this->pdo->prepare(
            'DELETE FROM document_tags WHERE document_id = :document_id'
        );

        $findTag = $this->pdo->prepare(
            'SELECT id
             FROM tags
             WHERE workspace_id = :workspace_id
               AND slug = :slug
             LIMIT 1'
        );

        $insertTag = $this->pdo->prepare(
            'INSERT INTO tags (workspace_id, name, slug)
             VALUES (:workspace_id, :name, :slug)'
        );

        $linkTag = $this->pdo->prepare(
            'INSERT IGNORE INTO document_tags (document_id, tag_id)
             VALUES (:document_id, :tag_id)'
        );

        $deleteLinks->execute([
            'document_id' => $documentId,
        ]);

        foreach ($names as $name) {
            $slug = Str::slug($name);

            $findTag->execute([
                'workspace_id' => $workspaceId,
                'slug' => $slug,
            ]);

            $tagId = $findTag->fetchColumn();

            if ($tagId === false) {
                $insertTag->execute([
                    'workspace_id' => $workspaceId,
                    'name' => $name,
                    'slug' => $slug,
                ]);

                $tagId = (int) $this->pdo->lastInsertId();
            }

            $linkTag->execute([
                'document_id' => $documentId,
                'tag_id' => (int) $tagId,
            ]);
        }
    }

    /**
     * Restituisce i tag già assegnati nel formato richiesto dai form:
     * "rete, vpn, howto".
     */
    public static function toCsv(array $tags): string
    {
        return implode(', ', array_map(
            static fn(array $tag): string => (string) ($tag['name'] ?? ''),
            $tags
        ));
    }
}