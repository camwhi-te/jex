<?php

namespace Everest\Http\Requests\Api\Client\Groups;

use Everest\Http\Requests\Api\Client\ClientApiRequest;

class UpdateServerGroupRequest extends ClientApiRequest
{
    /**
     * Both fields are optional; a missing or null value keeps the current one.
     */
    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'min:3', 'max:191'],
            'color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ];
    }
}
