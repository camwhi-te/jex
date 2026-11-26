<?php

namespace Everest\Http\Requests\Api\Application\Api;

use Everest\Models\AdminRole;
use Everest\Services\Acl\Api\AdminAcl;
use Everest\Http\Requests\Api\Application\ApplicationApiRequest;

class StoreApplicationApiKeyRequest extends ApplicationApiRequest
{
    public function rules(): array
    {
        $columns = self::permissionColumns();

        $rules = [
            'memo' => 'required|string|max:500',
            'permissions' => 'nullable|array:' . implode(',', $columns),
        ];

        foreach ($columns as $column) {
            $rules['permissions.' . $column] = 'sometimes|integer|min:0|max:3';
        }

        return $rules;
    }

    /**
     * Returns the legacy r_<resource> permission columns passed in the request. Only
     * known resource columns are returned so that this data can never overwrite any
     * other attribute of the API key being created.
     */
    public function getKeyPermissions(): array
    {
        $permissions = $this->input('permissions') ?? [];

        $arr = [];
        foreach (self::permissionColumns() as $column) {
            if (array_key_exists($column, $permissions)) {
                $arr[$column] = (int) $permissions[$column];
            }
        }

        return $arr;
    }

    public function permission(): string
    {
        return AdminRole::API_CREATE;
    }

    /**
     * Returns the r_<resource> column names for every resource in the admin ACL.
     */
    protected static function permissionColumns(): array
    {
        return array_map(
            fn (string $resource) => AdminAcl::COLUMN_IDENTIFIER . $resource,
            AdminAcl::getResourceList(),
        );
    }
}
