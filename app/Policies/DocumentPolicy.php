<?php
declare(strict_types=1);

namespace Archium\Policies;

final class DocumentPolicy
{
    public static function canView(?string $workspaceRole): bool
    {
        return WorkspacePolicy::canView($workspaceRole);
    }

    public static function canWrite(?string $workspaceRole): bool
    {
        return WorkspacePolicy::canEditContent($workspaceRole);
    }

    public static function canDelete(?string $workspaceRole): bool
    {
        return WorkspacePolicy::canEditContent($workspaceRole);
    }
}