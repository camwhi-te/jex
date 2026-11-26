<?php

namespace Everest\Http\Requests\Api\Client\Billing;

use Everest\Models\Permission;
use Everest\Http\Requests\Api\Client\ClientApiRequest;

class ProcessUpgradeRequest extends ClientApiRequest
{
    public function permission(): string
    {
        return Permission::ACTION_BILLING_UPDATE;
    }

    public function rules(): array
    {
        return [
            'product_id' => 'required|int|exists:products,id',
        ];
    }
}
