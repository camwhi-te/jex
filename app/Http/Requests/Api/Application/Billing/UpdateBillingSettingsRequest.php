<?php

namespace Everest\Http\Requests\Api\Application\Billing;

use Everest\Models\AdminRole;
use Illuminate\Validation\Rule;
use Everest\Http\Requests\Api\Application\ApplicationApiRequest;

class UpdateBillingSettingsRequest extends ApplicationApiRequest
{
    /**
     * The billing settings that can be updated, without the "modules:billing:" prefix.
     */
    public const KEYS = [
        'enabled', 'keys:secret', 'currency:code', 'currency:symbol',
        'links:terms', 'links:privacy', 'renewal:days', 'renewal:threshold',
        'allow_upgrades',
    ];

    public function rules(): array
    {
        return [
            'key' => ['required', 'string', Rule::in(self::KEYS)],
            'value' => ['present', 'nullable', function (string $attribute, mixed $value, \Closure $fail) {
                if (!is_scalar($value) && !is_null($value)) {
                    $fail('The value must be a string, number, boolean or null.');
                }
            }],
        ];
    }

    public function permission(): string
    {
        return AdminRole::BILLING_UPDATE;
    }
}
