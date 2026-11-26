<?php

namespace Everest\Http\Requests\Api\Client\Billing;

use Everest\Models\Permission;
use Everest\Http\Requests\Api\Client\ClientApiRequest;

class GetUpgradeOptionsRequest extends ClientApiRequest
{
    public function permission(): string
    {
        return Permission::ACTION_BILLING_READ;
    }
}
