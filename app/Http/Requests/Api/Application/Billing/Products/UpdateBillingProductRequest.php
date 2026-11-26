<?php

namespace Everest\Http\Requests\Api\Application\Billing\Products;

use Everest\Models\AdminRole;

class UpdateBillingProductRequest extends StoreBillingProductRequest
{
    public function permission(): string
    {
        return AdminRole::BILLING_PRODUCTS_UPDATE;
    }
}
