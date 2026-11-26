<?php

namespace Everest\Http\Requests\Api\Application\Nests;

use Everest\Models\Nest;
use Everest\Models\AdminRole;
use Everest\Http\Requests\Api\Application\ApplicationApiRequest;

class StoreNestRequest extends ApplicationApiRequest
{
    public function rules(?array $rules = null): array
    {
        $rules = $rules ?? Nest::getRules();

        // The author is optional. When it is not sent, the configured service author is used.
        $rules['author'] = ['sometimes', 'nullable', 'string', 'email'];

        return $rules;
    }

    public function permission(): string
    {
        return AdminRole::NESTS_CREATE;
    }
}
