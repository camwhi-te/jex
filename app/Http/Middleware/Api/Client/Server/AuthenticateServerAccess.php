<?php

namespace Everest\Http\Middleware\Api\Client\Server;

use Everest\Models\Server;
use Illuminate\Http\Request;
use Everest\Exceptions\Http\Server\ServerStateConflictException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AuthenticateServerAccess
{
    /**
     * Routes that this middleware should not apply to if the user is an admin.
     */
    protected array $except = [
        'api:client:server.ws',
    ];

    /**
     * AuthenticateServerAccess constructor.
     */
    public function __construct()
    {
    }

    /**
     * Authenticate that this server exists and is not suspended or marked as installing.
     */
    public function handle(Request $request, \Closure $next): mixed
    {
        /** @var \Everest\Models\User $user */
        $user = $request->user();
        $server = $request->route()->parameter('server');

        if (!$server instanceof Server) {
            throw new NotFoundHttpException(trans('exceptions.api.resource_not_found'));
        }

        // At the very least, ensure that the user trying to make this request is the
        // server owner, a subuser, or a root admin. We'll leave it up to the controllers
        // to authenticate more detailed permissions if needed.
        if ($user->id !== $server->owner_id && !$user->root_admin) {
            // Check for subuser status.
            if (!$server->subusers->contains('user_id', $user->id)) {
                throw new NotFoundHttpException(trans('exceptions.api.resource_not_found'));
            }
        }

        try {
            $server->validateCurrentState();
        } catch (ServerStateConflictException $exception) {
            // Always allow users to get information about their server, whatever state it is in.
            if (!$request->routeIs('api:client:server.view')) {
                $isSuspendedOrUnderMaintenance = $server->isSuspended() || $server->node->isUnderMaintenance();

                // Resource utilization remains available while the server is suspended or
                // its node is under maintenance.
                $allowResources = $isSuspendedOrUnderMaintenance && $request->routeIs('api:client:server.resources');

                // Root admins may still connect to the websocket while the server is installing,
                // restoring a backup or being transferred (but not when suspended or under maintenance).
                $allowAdmin = !$isSuspendedOrUnderMaintenance && $user->root_admin && $request->routeIs($this->except);

                if (!$allowResources && !$allowAdmin) {
                    throw $exception;
                }
            }
        }

        $request->attributes->set('server', $server);

        return $next($request);
    }
}
