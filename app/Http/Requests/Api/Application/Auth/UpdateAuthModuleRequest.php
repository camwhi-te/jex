<?php

namespace Everest\Http\Requests\Api\Application\Auth;

use Everest\Models\AdminRole;
use Illuminate\Validation\Rule;

class UpdateAuthModuleRequest extends AuthModuleRequest
{
    /**
     * Validation rules applied to the value of each module setting key.
     */
    protected const VALUE_RULES = [
        'enabled' => ['required', 'boolean'],
        'force2fa' => ['required', 'boolean'],
        'attempts' => ['required', 'integer', 'min:0'],
        'delay' => ['required', 'integer', 'min:0'],
        'sensitivity' => ['required', 'string', 'in:low,medium,high'],
        'client_id' => ['nullable', 'string'],
        'client_secret' => ['nullable', 'string'],
        'content' => ['nullable', 'string'],
    ];

    public function rules(): array
    {
        $module = $this->input('module');
        $keys = is_string($module) ? (self::MODULES[$module] ?? []) : [];
        $key = $this->input('key');

        return [
            'module' => ['required', 'string', Rule::in(array_keys(self::MODULES))],
            'key' => ['required', 'string', Rule::in($keys)],
            'value' => is_string($key) && in_array($key, $keys, true)
                ? self::VALUE_RULES[$key]
                : ['nullable'],
        ];
    }

    public function permission(): string
    {
        return AdminRole::AUTH_UPDATE;
    }
}
