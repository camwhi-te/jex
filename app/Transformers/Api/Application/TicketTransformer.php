<?php

namespace Everest\Transformers\Api\Application;

use Everest\Models\User;
use Everest\Models\Ticket;
use League\Fractal\Resource\Collection;
use Everest\Transformers\Api\Transformer;
use League\Fractal\Resource\NullResource;

class TicketTransformer extends Transformer
{
    /**
     * List of resources that can be included.
     */
    protected array $availableIncludes = ['messages'];

    public function getResourceName(): string
    {
        return Ticket::RESOURCE_NAME;
    }

    /**
     * Transform this model into a representation that can be consumed by a client.
     */
    public function transform(Ticket $model): array
    {
        return [
            'id' => $model->id,
            'title' => $model->title,
            'status' => $model->status,
            'user' => self::userSummary($model->user),
            'assigned_to' => self::userSummary($model->assignedTo),
            'created_at' => $model->created_at->toIso8601String(),
            'updated_at' => $model->updated_at->toIso8601String() ? $model->updated_at->toIso8601String() : null,
        ];
    }

    /**
     * Return the limited set of user fields exposed on tickets and ticket messages,
     * rather than the full user record.
     */
    public static function userSummary(?User $user): ?array
    {
        if (is_null($user)) {
            return null;
        }

        return [
            'id' => $user->id,
            'uuid' => $user->uuid,
            'username' => $user->username,
            'email' => $user->email,
        ];
    }

    /**
     * Return the messages associated with this ticket.
     */
    public function includeMessages(Ticket $ticket): Collection|NullResource
    {
        return $this->collection($ticket->messages, new TicketMessageTransformer());
    }
}
