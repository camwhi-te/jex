<?php

namespace Everest\Transformers\Api\Client;

use Everest\Models\User;
use Everest\Models\TicketMessage;
use Everest\Transformers\Api\Transformer;

class TicketMessageTransformer extends Transformer
{
    public function getResourceName(): string
    {
        return TicketMessage::RESOURCE_NAME;
    }

    /**
     * Transform this model into a representation that can be consumed by a client.
     */
    public function transform(TicketMessage $model): array
    {
        return [
            'id' => $model->id,
            'message' => $model->message,
            'author' => $this->author($model->user),
            'created_at' => $model->created_at->toIso8601String(),
            'updated_at' => $model->updated_at->toIso8601String() ? $model->updated_at->toIso8601String() : null,
        ];
    }

    /**
     * Only expose what the client needs to tell its own messages apart from staff
     * replies. A staff member's email address is never returned to the ticket owner.
     */
    protected function author(?User $user): ?array
    {
        if (is_null($user)) {
            return null;
        }

        return [
            'uuid' => $user->uuid,
            'email' => $user->is($this->request->user()) ? $user->email : null,
        ];
    }
}
