<?php

namespace Everest\Http\Requests\Api\Application\Billing\Config;

use Everest\Models\AdminRole;
use Everest\Http\Requests\Api\Application\ApplicationApiRequest;

class ImportBillingConfigRequest extends ApplicationApiRequest
{
    public function rules(): array
    {
        return [
            'override' => 'nullable|boolean',
            'ignore_duplicates' => 'nullable|boolean',

            'data' => 'required|array',

            'data.categories' => 'present|array',
            'data.categories.*' => 'array',
            'data.categories.*.uuid' => 'required|string',
            'data.categories.*.name' => 'required|string|min:3|max:191',
            'data.categories.*.icon' => 'nullable|string|max:300',
            'data.categories.*.description' => 'nullable|string|max:300',
            'data.categories.*.egg_id' => 'nullable|integer|exists:eggs,id',
            'data.categories.*.nest_id' => 'nullable|required_without:data.categories.*.egg_id|integer|exists:nests,id',

            'data.products' => 'present|array',
            'data.products.*' => 'array',
            'data.products.*.category_uuid' => 'required|string',
            'data.products.*.name' => 'required|string|min:3|max:191',
            'data.products.*.icon' => 'nullable|string|min:3|max:300',
            'data.products.*.price' => 'required|numeric|min:0',
            'data.products.*.description' => 'nullable|string|max:300',
            'data.products.*.cpu_limit' => 'required|integer|min:0',
            'data.products.*.memory_limit' => 'required|integer',
            'data.products.*.disk_limit' => 'required|integer',
            'data.products.*.backup_limit' => 'required|integer|min:0',
            'data.products.*.database_limit' => 'required|integer|min:0',
            'data.products.*.allocation_limit' => 'required|integer|min:0',
        ];
    }

    public function permission(): string
    {
        return AdminRole::BILLING_IMPORT;
    }
}
