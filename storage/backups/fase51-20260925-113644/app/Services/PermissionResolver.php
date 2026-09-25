<?php
declare(strict_types=1);

namespace Archium\Services;

use Archium\Repositories\CollectionRepository;
use Archium\Repositories\DocumentRepository;
use Archium\Repositories\WorkspaceRepository;

/**
 * Risolutore centralizzato dei permessi Fase 5.
 *
 * Ordine di precedenza:
 * 1. owner del workspace -> admin assoluto;
 * 2. override esplicito sul documento;
 * 3. override esplicito sulla raccolta del documento;
 * 4. ruolo membership workspace.
 *
 * Livelli: read < write < admin. L'assenza di membership nega accesso.
 */
final class PermissionResolver
{
    private const LEVELS = ['read' => 1, 'write' => 2, 'admin' => 3];

    public function __construct(
        private WorkspaceRepository $workspaces,
        private CollectionRepository $collections,
        private DocumentRepository $documents
    ) {
    }

    public function workspaceRole(int $workspaceId, int $userId): ?string
    {
        return $this->workspaces->roleOf($workspaceId, $userId);
    }

    public function canWorkspace(int $workspaceId, int $userId, string $needed): bool
    {
        $role = $this->workspaceRole($workspaceId, $userId);
        return $this->allowsWorkspaceRole($role, $needed);
    }

    public function canCollection(int $collectionId, int $workspaceId, int $userId, string $needed): bool
    {
        $workspaceRole = $this->workspaceRole($workspaceId, $userId);
        if ($workspaceRole === null) {
            return false;
        }
        if ($workspaceRole === 'owner') {
            return true;
        }

        $override = $this->collections->memberPermission($collectionId, $userId);
        if ($override !== null) {
            return $this->allowsPermission($override, $needed);
        }

        return $this->allowsWorkspaceRole($workspaceRole, $needed);
    }

    public function canDocument(int $documentId, int $workspaceId, ?int $collectionId, int $userId, string $needed): bool
    {
        $workspaceRole = $this->workspaceRole($workspaceId, $userId);
        if ($workspaceRole === null) {
            return false;
        }
        if ($workspaceRole === 'owner') {
            return true;
        }

        $documentOverride = $this->documents->userPermission($documentId, $userId);
        if ($documentOverride !== null) {
            return $this->allowsPermission($documentOverride, $needed);
        }

        if ($collectionId !== null) {
            $collectionOverride = $this->collections->memberPermission($collectionId, $userId);
            if ($collectionOverride !== null) {
                return $this->allowsPermission($collectionOverride, $needed);
            }
        }

        return $this->allowsWorkspaceRole($workspaceRole, $needed);
    }

    public function canManageWorkspace(int $workspaceId, int $userId): bool
    {
        return $this->workspaceRole($workspaceId, $userId) === 'owner';
    }

    private function allowsWorkspaceRole(?string $role, string $needed): bool
    {
        if ($role === null) {
            return false;
        }

        $permission = match ($role) {
            'owner' => 'admin',
            'editor' => 'write',
            'viewer' => 'read',
            default => null,
        };

        return $permission !== null && $this->allowsPermission($permission, $needed);
    }

    private function allowsPermission(string $actual, string $needed): bool
    {
        return (self::LEVELS[$actual] ?? 0) >= (self::LEVELS[$needed] ?? 999);
    }
}