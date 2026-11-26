<?php

namespace Everest\Services\Eggs\Variables;

use Everest\Models\Egg;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Everest\Models\EggVariable;
use Everest\Exceptions\DisplayException;
use Everest\Traits\Services\ValidatesValidationRules;
use Illuminate\Contracts\Validation\Factory as ValidationFactory;
use Everest\Exceptions\Service\Egg\Variable\ReservedVariableNameException;

class VariableUpdateService
{
    use ValidatesValidationRules;

    /**
     * VariableUpdateService constructor.
     */
    public function __construct(private ValidationFactory $validator)
    {
    }

    /**
     * Return the validation factory instance to be used by rule validation
     * checking in the trait.
     */
    protected function getValidator(): ValidationFactory
    {
        return $this->validator;
    }

    /**
     * Update a specific egg variable.
     *
     * @throws DisplayException
     * @throws ReservedVariableNameException
     */
    public function handle(Egg $egg, array $data): void
    {
        if (!is_null(array_get($data, 'env_variable'))) {
            if (in_array(strtoupper(array_get($data, 'env_variable')), explode(',', EggVariable::RESERVED_ENV_NAMES))) {
                throw new ReservedVariableNameException(trans('exceptions.nest.variables.reserved_name', ['name' => array_get($data, 'env_variable')]));
            }

            $count = $egg->variables()
                ->where('egg_variables.env_variable', $data['env_variable'])
                ->where('egg_variables.id', '!=', $data['id'])
                ->count();

            if ($count > 0) {
                throw new DisplayException(trans('exceptions.nest.variables.env_not_unique', ['name' => array_get($data, 'env_variable')]));
            }
        }

        if (!empty($data['rules'] ?? '')) {
            $this->validateRules(
                (is_string($data['rules']) && Str::contains($data['rules'], ';;'))
                    ? explode(';;', $data['rules'])
                    : $data['rules']
            );
        }

        // Only update the columns that were sent, so omitted fields keep their current values.
        $update = Arr::only($data, ['name', 'description', 'env_variable', 'default_value', 'user_viewable', 'user_editable', 'rules']);
        foreach (['description', 'default_value'] as $key) {
            if (array_key_exists($key, $update) && is_null($update[$key])) {
                $update[$key] = '';
            }
        }

        if (empty($update)) {
            return;
        }

        $egg->variables()->where('egg_variables.id', $data['id'])->update($update);
    }
}
