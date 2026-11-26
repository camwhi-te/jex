<?php

namespace Everest\Http\Requests\Api\Client\Account;

use Everest\Http\Requests\Api\Client\ClientApiRequest;

class UpdateAvatarRequest extends ClientApiRequest
{
    public function rules(): array
    {
        // One of the two must be provided; removing an avatar is done with the DELETE endpoint.
        return [
            'avatar_url' => ['required_without:avatar', 'nullable', 'string', 'max:500', 'url'],
            'avatar' => ['required_without:avatar_url', 'nullable', 'image', 'max:2048'],
        ];
    }
}
