<?php

namespace Everest\Http\Requests\Api\Application\Intelligence;

use Everest\Models\AdminRole;
use Everest\Http\Requests\Api\Application\ApplicationApiRequest;

class UpdateIntelligenceSettingsRequest extends ApplicationApiRequest
{
    public function rules(): array
    {
        return [
            'enabled' => 'nullable|bool',
            // The Panel frontend sends a boolean here (whether a key is set) when it only
            // means to change another setting; booleans are accepted and then ignored.
            'key' => is_bool($this->input('key')) ? 'nullable|bool' : 'nullable|string',
            'user_access' => 'nullable|bool',
        ];
    }

    public function permission(): string
    {
        return AdminRole::AI_UPDATE;
    }
}
