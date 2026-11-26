<?php

namespace Everest\Http\Requests\Api\Client\Servers\Databases;

use Everest\Models\Server;
use Everest\Models\Database;
use Webmozart\Assert\Assert;
use Everest\Models\Permission;
use Everest\Contracts\Http\ClientPermissionsRequest;
use Everest\Http\Requests\Api\Client\ClientApiRequest;
use Everest\Services\Databases\DatabaseManagementService;

class StoreDatabaseRequest extends ClientApiRequest implements ClientPermissionsRequest
{
    public function permission(): string
    {
        return Permission::ACTION_DATABASE_CREATE;
    }

    public function rules(): array
    {
        $server = $this->route()->parameter('server');

        Assert::isInstanceOf($server, Server::class);

        return [
            'database' => [
                'required',
                'alpha_dash',
                'min:3',
                'max:48',
                // Yes, I am aware that you could have the same database name across two unique hosts. However,
                // I don't really care about that for this validation. We just want to make sure it is unique to
                // the server itself. No need for complexity.
                //
                // The stored name is prefixed with "s{server_id}_", so compare against the prefixed name
                // rather than the raw input value.
                function (string $attribute, mixed $value, \Closure $fail) use ($server) {
                    if (!is_string($value)) {
                        return;
                    }

                    $exists = Database::query()
                        ->where('server_id', $server->id)
                        ->where('database', DatabaseManagementService::generateUniqueDatabaseName($value, $server->id))
                        ->exists();

                    if ($exists) {
                        $fail('The database name you have selected is already in use by this server.');
                    }
                },
            ],
            'remote' => Database::getRulesForField('remote'),
        ];
    }
}
