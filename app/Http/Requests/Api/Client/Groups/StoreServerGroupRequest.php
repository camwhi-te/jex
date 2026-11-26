<?php

namespace Everest\Http\Requests\Api\Client\Groups;

use Everest\Http\Requests\Api\Client\ClientApiRequest;

class StoreServerGroupRequest extends ClientApiRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:3', 'max:191'],
            'color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ];
    }
}
