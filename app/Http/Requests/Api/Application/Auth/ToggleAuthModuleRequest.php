<?php

namespace Everest\Http\Requests\Api\Application\Auth;

use Illuminate\Validation\Rule;

abstract class ToggleAuthModuleRequest extends AuthModuleRequest
{
    public function rules(): array
    {
        return [
            'module' => ['required', 'string', Rule::in(self::toggleableModules())],
        ];
    }

    /**
     * Older clients (including earlier versions of the Panel frontend) send the module
     * name as a bare JSON string rather than as an object, so map that to "module".
     */
    protected function prepareForValidation()
    {
        parent::prepareForValidation();

        if (!$this->has('module') && is_string($this->input('0'))) {
            $this->merge(['module' => $this->input('0')]);
        }
    }
}
