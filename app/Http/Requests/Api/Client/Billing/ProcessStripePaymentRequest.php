<?php

namespace Everest\Http\Requests\Api\Client\Billing;

use Everest\Http\Requests\Api\Client\ClientApiRequest;

class ProcessStripePaymentRequest extends ClientApiRequest
{
    public function rules(): array
    {
        return [
            'session' => 'required|string|max:255',
        ];
    }
}
