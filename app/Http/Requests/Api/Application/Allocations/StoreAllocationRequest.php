<?php

namespace Everest\Http\Requests\Api\Application\Allocations;

use Everest\Models\AdminRole;
use Everest\Http\Requests\Api\Application\ApplicationApiRequest;

class StoreAllocationRequest extends ApplicationApiRequest
{
    public function rules(): array
    {
        return [
            'ip' => 'required|string',
            'alias' => 'sometimes|nullable|string|max:191',
            'start_port' => 'required|integer|between:1025,65535',
            'end_port' => 'sometimes|nullable|integer|between:1025,65535|gte:start_port',
        ];
    }

    public function permission(): string
    {
        return AdminRole::NODES_UPDATE;
    }
}
