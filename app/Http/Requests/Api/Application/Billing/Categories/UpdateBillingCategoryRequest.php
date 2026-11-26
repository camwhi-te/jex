<?php

namespace Everest\Http\Requests\Api\Application\Billing\Categories;

use Everest\Models\AdminRole;

class UpdateBillingCategoryRequest extends StoreBillingCategoryRequest
{
    public function permission(): string
    {
        return AdminRole::BILLING_CATEGORIES_UPDATE;
    }
}
