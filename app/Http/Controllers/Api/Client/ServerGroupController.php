<?php

namespace Everest\Http\Controllers\Api\Client;

use Everest\Models\Server;
use Illuminate\Http\Response;
use Everest\Models\ServerGroup;
use Illuminate\Support\Facades\DB;
use Everest\Exceptions\DisplayException;
use Everest\Http\Requests\Api\Client\ClientApiRequest;
use Everest\Transformers\Api\Client\ServerGroupTransformer;
use Everest\Http\Requests\Api\Client\Groups\StoreServerGroupRequest;
use Everest\Http\Requests\Api\Client\Groups\ServerGroupServerRequest;
use Everest\Http\Requests\Api\Client\Groups\UpdateServerGroupRequest;

class ServerGroupController extends ClientApiController
{
    /**
     * Returns all the server groups that exist for the given client.
     */
    public function index(ClientApiRequest $request): array
    {
        return $this->transform($request->user()->serverGroups, ServerGroupTransformer::class);
    }

    /**
     * Create a new server group and store in the database.
     */
    public function store(StoreServerGroupRequest $request): array
    {
        $group = ServerGroup::create([
            'user_id' => $request->user()->id,
            'name' => $request->validated('name'),
            'color' => $request->validated('color'),
        ]);

        return $this->transform($group, ServerGroupTransformer::class);
    }

    /**
     * Add a server to the selected group.
     */
    public function add(ServerGroupServerRequest $request, int $id): Response
    {
        $group = ServerGroup::where('id', $id)->where('user_id', $request->user()->id)->first();

        if (!$group) {
            throw new DisplayException('No group with that ID exists on your account.');
        }

        $server = Server::where('uuid', $request->validated('server'))
            ->where('owner_id', $request->user()->id)
            ->first();

        if (!$server) {
            throw new DisplayException('No server with that UUID exists on your account.');
        }

        $server->update(['group_id' => $group->id]);

        return $this->returnNoContent();
    }

    /**
     * Remove a server from the selected group.
     */
    public function remove(ServerGroupServerRequest $request, int $id): Response
    {
        $server = Server::where('uuid', $request->validated('server'))
            ->where('owner_id', $request->user()->id)
            ->where('group_id', $id)
            ->first();

        if (!$server) {
            throw new DisplayException('No server with that UUID exists in that group on your account.');
        }

        $server->update(['group_id' => null]);

        return $this->returnNoContent();
    }

    /**
     * Update a selected server group.
     */
    public function update(UpdateServerGroupRequest $request, int $id): Response
    {
        $group = ServerGroup::findOrFail($id);

        if ($group->user_id !== $request->user()->id) {
            throw new DisplayException('You do not have permission to edit this server group.');
        }

        $group->update([
            'name' => $request->validated('name') ?? $group->name,
            'color' => $request->validated('color') ?? $group->color,
        ]);

        return $this->returnNoContent();
    }

    /**
     * Delete a selected server group.
     */
    public function delete(ClientApiRequest $request, int $id): Response
    {
        $group = ServerGroup::findOrFail($id);

        if ($group->user_id !== $request->user()->id) {
            throw new DisplayException('You do not have permission to edit this server group.');
        }

        DB::transaction(function () use ($group) {
            // There is no foreign key on servers.group_id, so release the servers here.
            // Otherwise they would point at a missing group and fail validation on save.
            Server::query()->where('group_id', $group->id)->update(['group_id' => null]);

            $group->delete();
        });

        return $this->returnNoContent();
    }
}
