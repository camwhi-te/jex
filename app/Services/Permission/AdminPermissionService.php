<?php

namespace Everest\Services\Permission;

use Everest\Models\User;

class AdminPermissionService
{
    /**
     * Get the permissions associated with the admin user, as a flat list of
     * permission strings. Root administrators receive ["*"].
     */
    public function handle(User $user): array
    {
        return $user->admin_permissions;
    }
}
