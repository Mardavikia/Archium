<?php
declare(strict_types=1);

namespace Archium\Policies;

final class CollectionPolicy
{
    public static function canView(?string $workspaceRole): bool
    {
        return WorkspacePolicy::canView($workspaceRole);
    }

    public static function canWrite(?string $workspaceRole): bool
    {
        return WorkspacePolicy::canEditContent($workspaceRole);
    }
}