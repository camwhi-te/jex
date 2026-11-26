<?php

namespace Everest\Http\Requests\Api\Application\Webhooks;

use Everest\Models\AdminRole;
use Everest\Http\Requests\Api\Application\ApplicationApiRequest;

class UpdateWebhookSettingsRequest extends ApplicationApiRequest
{
    public function rules(): array
    {
        return [
            'key' => 'required|string|in:enabled,url',
            'value' => $this->input('key') === 'enabled'
                ? 'required|boolean'
                : 'nullable|string|url',
        ];
    }

    public function permission(): string
    {
        return AdminRole::WEBHOOKS_UPDATE;
    }
}
