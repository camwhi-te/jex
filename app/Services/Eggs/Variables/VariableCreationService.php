<?php

namespace Everest\Services\Eggs\Variables;

use Everest\Models\EggVariable;
use Everest\Exceptions\DisplayException;
use Everest\Traits\Services\ValidatesValidationRules;
use Everest\Contracts\Repository\EggVariableRepositoryInterface;
use Illuminate\Contracts\Validation\Factory as ValidationFactory;
use Everest\Exceptions\Service\Egg\Variable\ReservedVariableNameException;

class VariableCreationService
{
    use ValidatesValidationRules;

    /**
     * VariableCreationService constructor.
     */
    public function __construct(private EggVariableRepositoryInterface $repository, private ValidationFactory $validator)
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
     * Create a new variable for a given Egg.
     *
     * @throws \Everest\Exceptions\Model\DataValidationException
     * @throws \Everest\Exceptions\Service\Egg\Variable\BadValidationRuleException
     * @throws ReservedVariableNameException
     */
    public function handle(int $egg, array $data): EggVariable
    {
        if (in_array(strtoupper(array_get($data, 'env_variable')), explode(',', EggVariable::RESERVED_ENV_NAMES))) {
            throw new ReservedVariableNameException(sprintf('Cannot use the protected name %s for this environment variable.', array_get($data, 'env_variable')));
        }

        $exists = EggVariable::query()
            ->where('egg_id', $egg)
            ->where('env_variable', array_get($data, 'env_variable'))
            ->exists();

        if ($exists) {
            throw new DisplayException(trans('exceptions.nest.variables.env_not_unique', ['name' => array_get($data, 'env_variable')]));
        }

        if (!empty($data['rules'] ?? '')) {
            $this->validateRules($data['rules']);
        }

        return $this->repository->create([
            'egg_id' => $egg,
            'name' => $data['name'] ?? '',
            'description' => $data['description'] ?? '',
            'env_variable' => $data['env_variable'] ?? '',
            'default_value' => $data['default_value'] ?? '',
            'user_viewable' => (bool) ($data['user_viewable'] ?? false),
            'user_editable' => (bool) ($data['user_editable'] ?? false),
            'rules' => $data['rules'] ?? '',
        ]);
    }
}
