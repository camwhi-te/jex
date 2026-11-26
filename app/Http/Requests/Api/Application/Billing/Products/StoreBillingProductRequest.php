<?php

namespace Everest\Http\Requests\Api\Application\Billing\Products;

use Everest\Models\AdminRole;
use Everest\Http\Requests\Api\Application\ApplicationApiRequest;

class StoreBillingProductRequest extends ApplicationApiRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|min:3|max:191',
            'icon' => 'nullable|string|min:3|max:300',
            'price' => 'required|numeric|min:0',
            'description' => 'nullable|string|max:300',
            'limits' => 'required|array',
            'limits.cpu' => 'required|integer|min:0',
            'limits.memory' => 'required|integer',
            'limits.disk' => 'required|integer',
            'limits.backup' => 'required|integer|min:0',
            'limits.database' => 'required|integer|min:0',
            'limits.allocation' => 'required|integer|min:0',
        ];
    }

    public function permission(): string
    {
        return AdminRole::BILLING_PRODUCTS_CREATE;
    }
}
