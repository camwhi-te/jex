<?php

namespace Everest\Http\Requests\Api\Application\Auth;

use Everest\Models\AdminRole;

class DisableAuthModuleRequest extends ToggleAuthModuleRequest
{
    public function permission(): string
    {
        return AdminRole::AUTH_DELETE;
    }
}
