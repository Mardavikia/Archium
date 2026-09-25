<?php
declare(strict_types=1);

namespace Archium\Policies;

/**
 * Matrice Fase 2 (semplificata): i permessi granulari per raccolta/documento
 * (collection_members, document_permissions) saranno implementati in Fase 5.
 */
final class WorkspacePolicy
{
    public static function canView(?string $role): bool
    {
        return $role !== null; // owner/editor/viewer vedono tutto il workspace
    }

    public static function canEditContent(?string $role): bool
    {
        return in_array($role, ['owner', 'editor'], true);
    }

    public static function canManage(?string $role): bool
    {
        return $role === 'owner';
    }
}