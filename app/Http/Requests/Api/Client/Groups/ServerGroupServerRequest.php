<?php

namespace Everest\Http\Requests\Api\Client\Groups;

use Everest\Http\Requests\Api\Client\ClientApiRequest;

class ServerGroupServerRequest extends ClientApiRequest
{
    public function rules(): array
    {
        return [
            'server' => ['required', 'string'],
        ];
    }
}
