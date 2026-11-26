<?php

namespace Everest\Services\Servers;

use Everest\Models\Server;
use Illuminate\Support\Arr;
use Illuminate\Database\ConnectionInterface;
use Everest\Traits\Services\ReturnsUpdatedModels;
use Everest\Repositories\Wings\DaemonRevocationRepository;
use Everest\Exceptions\Http\Connection\DaemonConnectionException;

class DetailsModificationService
{
    use ReturnsUpdatedModels;

    /**
     * DetailsModificationService constructor.
     */
    public function __construct(
        private ConnectionInterface $connection,
        private DaemonRevocationRepository $revocationRepository,
    ) {
    }

    /**
     * Update the details for a single server instance.
     *
     * @throws \Throwable
     */
    public function handle(Server $server, array $data): Server
    {
        return $this->connection->transaction(function () use ($data, $server) {
            $original = $server->user;

            // Only update the fields that were provided, so omitted fields keep their current values.
            $details = Arr::only($data, ['external_id', 'owner_id', 'name', 'renewal_date', 'billing_product_id']);
            if (array_key_exists('description', $data)) {
                $details['description'] = $data['description'] ?? '';
            }

            $server->forceFill($details)->saveOrFail();

            // If the owner_id value is changed we need to revoke any tokens that exist for the server
            // on the Wings instance so that the old owner no longer has any permission to access the
            // websockets.
            if (!$server->refresh()->user->is($original)) {
                try {
                    $this->revocationRepository->setNode($server->node)->deauthorize(
                        $original->uuid,
                        [$server->uuid],
                    );
                } catch (DaemonConnectionException $exception) {
                    // Do nothing. A failure here is not ideal, but it is likely to be caused by Wings
                    // being offline, or in an entirely broken state. Remember, these tokens reset every
                    // few minutes by default, we're just trying to help it along a little quicker.
                }
            }

            return $server;
        });
    }
}
