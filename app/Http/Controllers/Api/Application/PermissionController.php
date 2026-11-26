<?php

namespace Everest\Http\Controllers\Api\Application;

use Illuminate\Http\Request;
use Everest\Services\Permission\AdminPermissionService;

class PermissionController extends ApplicationApiController
{
    /**
     * PermissionController constructor.
     */
    public function __construct(
        private AdminPermissionService $permissionService,
    ) {
        parent::__construct();
    }

    /**
     * Returns the admin permissions granted to the authenticated user.
     */
    public function __invoke(Request $request): array
    {
        return [
            'object' => 'admin_permissions',
            'attributes' => [
                'permissions' => $this->permissionService->handle($request->user()),
            ],
        ];
    }
}
