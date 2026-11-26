<?php

namespace Everest\Http\Requests\Api\Client\Account;

use Everest\Models\User;
use Everest\Exceptions\DisplayException;
use Everest\Http\Requests\Api\Client\ClientApiRequest;

class SetupUserRequest extends ClientApiRequest
{
    /**
     * Accounts registered through an OAuth module are created with a placeholder
     * username carrying this prefix until the user finishes setting them up.
     */
    public const PENDING_USERNAME_PREFIX = 'null_user_';

    /**
     * @throws DisplayException
     */
    public function authorize(): bool
    {
        if (!parent::authorize()) {
            return false;
        }

        // Setup sets a new password without asking for the current one, so it must only
        // be usable while the account is still waiting to be set up.
        if (!str_starts_with($this->user()->username, self::PENDING_USERNAME_PREFIX)) {
            throw new DisplayException('This account has already been set up.');
        }

        return true;
    }

    public function rules(): array
    {
        $rules = User::getRulesForUpdate($this->user());

        return [
            // The new username must not look like a placeholder, otherwise setup would stay open.
            'username' => array_merge($rules['username'], ['string', 'min:3', 'not_regex:/^null_user_/i']),
            'password' => ['required', 'string', 'min:8'],
        ];
    }
}
