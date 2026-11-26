<?php

namespace Everest\Http\Requests\Api\Application\Theme;

class UpdateThemeColorRequest extends UpdateThemeRequest
{
    public function rules(): array
    {
        return [
            'key' => 'required|string|in:primary,secondary,background,headers,sidebar',
            'value' => ['required', 'string', 'regex:/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{4}|[0-9a-fA-F]{6}|[0-9a-fA-F]{8})$/'],
        ];
    }
}
