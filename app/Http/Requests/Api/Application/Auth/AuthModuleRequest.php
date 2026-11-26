<?php

namespace Everest\Http\Requests\Api\Application\Auth;

use Everest\Http\Requests\Api\Application\ApplicationApiRequest;

abstract class AuthModuleRequest extends ApplicationApiRequest
{
    /**
     * The authentication modules and the setting keys that the Panel loads for
     * each of them (see SettingsServiceProvider).
     */
    public const MODULES = [
        'registration' => ['enabled'],
        'security' => ['force2fa', 'attempts'],
        'discord' => ['enabled', 'client_id', 'client_secret'],
        'google' => ['enabled', 'client_id', 'client_secret'],
        'onboarding' => ['enabled', 'content'],
        'jguard' => ['enabled', 'delay', 'sensitivity'],
    ];

    /**
     * Setting keys whose values are secrets and must not be written to the activity log.
     */
    public const SECRET_KEYS = ['client_secret'];

    /**
     * Returns the modules which can be enabled or disabled.
     */
    public static function toggleableModules(): array
    {
        return array_keys(array_filter(self::MODULES, fn (array $keys) => in_array('enabled', $keys, true)));
    }
}
