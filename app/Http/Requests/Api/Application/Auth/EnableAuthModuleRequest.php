<?php

namespace Everest\Http\Requests\Api\Application\Auth;

use Everest\Models\AdminRole;

class EnableAuthModuleRequest extends ToggleAuthModuleRequest
{
    public function permission(): string
    {
        return AdminRole::AUTH_CREATE;
    }
}
