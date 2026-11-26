<?php

namespace Everest\Http\Controllers\Api\Application\Roles;

use Everest\Models\User;
use Everest\Facades\Activity;
use Everest\Models\AdminRole;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\QueryBuilder;
use Everest\Exceptions\Http\QueryValueOutOfRangeHttpException;
use Everest\Transformers\Api\Application\AdminRoleTransformer;
use Everest\Http\Requests\Api\Application\Roles\GetRoleRequest;
use Everest\Http\Requests\Api\Application\Roles\GetRolesRequest;
use Everest\Http\Requests\Api\Application\Roles\StoreRoleRequest;
use Everest\Http\Requests\Api\Application\Roles\DeleteRoleRequest;
use Everest\Http\Requests\Api\Application\Roles\UpdateRoleRequest;
use Everest\Http\Requests\Api\Application\Roles\UpdateRolePermissionsRequest;
use Everest\Http\Controllers\Api\Application\ApplicationApiController;

class RoleController extends ApplicationApiController
{
    /**
     * RoleController constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Returns an array of all roles.
     */
    public function index(GetRolesRequest $request): array
    {
        $perPage = (int) $request->query('per_page', '20');
        if ($perPage < 1 || $perPage > 100) {
            throw new QueryValueOutOfRangeHttpException('per_page', 1, 100);
        }

        $roles = QueryBuilder::for(AdminRole::query())
            ->allowedFilters(...['id', 'name'])
            ->allowedSorts(...['id', 'name'])
            ->paginate($perPage);

        return $this->transform($roles, AdminRoleTransformer::class);
    }

    /**
     * Returns a single role.
     */
    public function view(GetRoleRequest $request, AdminRole $role): array
    {
        return $this->transform($role, AdminRoleTransformer::class);
    }

    /**
     * Returns all of the available admin permissions assignable to users.
     */
    public function permissions(GetRoleRequest $request): array
    {
        return [
            'object' => 'role_permissions',
            'attributes' => [
                'permissions' => AdminRole::permissions(),
            ],
        ];
    }

    /**
     * Creates a new role.
     */
    public function store(StoreRoleRequest $request): array
    {
        $data = array_merge($this->filterPermissions($request->validated()), [
            'sort_id' => 99,
        ]);
        $role = AdminRole::query()->create($data);

        Activity::event('admin:roles:create')
            ->subject($role)
            ->property('role', $role)
            ->description('An administrator role was created')
            ->log();

        return $this->transform($role, AdminRoleTransformer::class);
    }

    /**
     * Updates a role.
     */
    public function update(UpdateRoleRequest $request, AdminRole $role): array
    {
        $role->update($this->filterPermissions($request->validated()));

        Activity::event('admin:roles:update')
            ->subject($role)
            ->property('role', $role)
            ->property('new_data', $request->all())
            ->description('An administrator role was updated')
            ->log();

        return $this->transform($role, AdminRoleTransformer::class);
    }

    /**
     * Updates the assigned permissions to a role.
     */
    public function updatePermissions(UpdateRolePermissionsRequest $request, AdminRole $role): array
    {
        $role->update([
            'permissions' => array_values(array_intersect($request->validated('permissions') ?? [], AdminRole::permissionStrings())),
        ]);

        Activity::event('admin:roles:update-permissions')
            ->subject($role)
            ->property('role', $role)
            ->description('Permissions were updated for an administrator role')
            ->log();

        return $this->transform($role, AdminRoleTransformer::class);
    }

    /**
     * Deletes a role.
     *
     * @throws \Exception
     */
    public function delete(DeleteRoleRequest $request, AdminRole $role): Response
    {
        DB::transaction(function () use ($role) {
            User::where('admin_role_id', $role->id)->update(['admin_role_id' => null, 'root_admin' => false]);

            $role->delete();
        });

        Activity::event('admin:roles:delete')
            ->subject($role)
            ->property('role', $role)
            ->description('An administrator role was deleted')
            ->log();

        return $this->returnNoContent();
    }

    /**
     * Removes any permission strings that are not assignable admin permissions.
     */
    private function filterPermissions(array $data): array
    {
        if (isset($data['permissions']) && is_array($data['permissions'])) {
            $data['permissions'] = array_values(array_intersect($data['permissions'], AdminRole::permissionStrings()));
        }

        return $data;
    }
}
