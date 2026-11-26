<?php

namespace Everest\Http\Requests\Api\Application\Servers;

use Everest\Models\Server;
use Illuminate\Support\Arr;
use Everest\Models\AdminRole;
use Everest\Http\Requests\Api\Application\ApplicationApiRequest;

class UpdateServerRequest extends ApplicationApiRequest
{
    public function rules(): array
    {
        $rules = Server::getRulesForUpdate($this->route()->parameter('server'));

        return [
            'external_id' => $rules['external_id'],
            'name' => $rules['name'],
            'description' => array_merge(['nullable'], $rules['description']),
            'owner_id' => $rules['owner_id'],

            'limits' => 'sometimes|array',
            'limits.memory' => $rules['memory'],
            'limits.swap' => $rules['swap'],
            'limits.disk' => $rules['disk'],
            'limits.io' => $rules['io'],
            'limits.threads' => $rules['threads'],
            'limits.cpu' => $rules['cpu'],
            'limits.oom_killer' => 'sometimes|boolean',

            'feature_limits' => 'required|array',
            'feature_limits.allocations' => $rules['allocation_limit'],
            'feature_limits.backups' => $rules['backup_limit'],
            'feature_limits.databases' => $rules['database_limit'],
            'feature_limits.subusers' => $rules['subuser_limit'],

            'renewal_date' => $rules['renewal_date'],
            'billing_product_id' => $rules['billing_product_id'],

            'allocation_id' => 'bail|exists:allocations,id',
            'add_allocations' => 'bail|array',
            'add_allocations.*' => 'integer',
            'remove_allocations' => 'bail|array',
            'remove_allocations.*' => 'integer',
        ];
    }

    /**
     * @param string|null $key
     * @param string|array|null $default
     */
    public function validated($key = null, $default = null)
    {
        $data = parent::validated();

        // Only return the fields that were sent, so that omitted fields are not
        // overwritten with null values when the server is updated.
        $map = [
            'external_id' => 'external_id',
            'name' => 'name',
            'description' => 'description',
            'owner_id' => 'owner_id',

            'memory' => 'limits.memory',
            'swap' => 'limits.swap',
            'disk' => 'limits.disk',
            'io' => 'limits.io',
            'threads' => 'limits.threads',
            'cpu' => 'limits.cpu',
            'oom_killer' => 'limits.oom_killer',

            'allocation_limit' => 'feature_limits.allocations',
            'backup_limit' => 'feature_limits.backups',
            'database_limit' => 'feature_limits.databases',
            'subuser_limit' => 'feature_limits.subusers',

            'renewal_date' => 'renewal_date',
            'billing_product_id' => 'billing_product_id',

            'allocation_id' => 'allocation_id',
            'add_allocations' => 'add_allocations',
            'remove_allocations' => 'remove_allocations',
        ];

        $response = [];
        foreach ($map as $field => $source) {
            if (Arr::has($data, $source)) {
                $response[$field] = Arr::get($data, $source);
            }
        }

        return is_null($key) ? $response : Arr::get($response, $key, $default);
    }

    public function permission(): string
    {
        return AdminRole::SERVERS_UPDATE;
    }
}
