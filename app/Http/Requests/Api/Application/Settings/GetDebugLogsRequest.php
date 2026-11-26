<?php

namespace Everest\Http\Requests\Api\Application\Settings;

use Everest\Models\AdminRole;
use Everest\Http\Requests\Api\Application\ApplicationApiRequest;

class GetDebugLogsRequest extends ApplicationApiRequest
{
    public function permission(): string
    {
        return AdminRole::SETTINGS_READ;
    }
}
