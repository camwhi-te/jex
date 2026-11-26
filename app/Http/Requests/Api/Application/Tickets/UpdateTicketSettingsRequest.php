<?php

namespace Everest\Http\Requests\Api\Application\Tickets;

use Everest\Models\AdminRole;
use Illuminate\Validation\Rule;
use Everest\Http\Requests\Api\Application\ApplicationApiRequest;

class UpdateTicketSettingsRequest extends ApplicationApiRequest
{
    /**
     * The ticket settings that can be updated, without the "modules:tickets:" prefix.
     */
    public const KEYS = ['enabled', 'max_count'];

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
        return AdminRole::TICKETS_UPDATE;
    }
}
