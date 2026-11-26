<?php

namespace Everest\Http\Requests\Api\Application\Nests;

use Everest\Models\Nest;
use Everest\Models\AdminRole;

class UpdateNestRequest extends StoreNestRequest
{
    public function rules(?array $rules = null): array
    {
        $rules = $rules ?? Nest::getRulesForUpdate($this->route()->parameter('nest'));

        // A nest's author cannot be changed, so it is optional and ignored.
        $rules['author'] = ['sometimes', 'nullable', 'string', 'email'];

        return $rules;
    }

    public function permission(): string
    {
        return AdminRole::NESTS_UPDATE;
    }
}
