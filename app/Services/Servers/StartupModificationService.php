<?php

namespace Everest\Services\Servers;

use Everest\Models\Egg;
use Everest\Models\User;
use Everest\Models\Server;
use Illuminate\Support\Arr;
use Everest\Models\EggVariable;
use Everest\Models\ServerVariable;
use Everest\Traits\Services\HasUserLevels;
use Illuminate\Database\ConnectionInterface;

class StartupModificationService
{
    use HasUserLevels;

    /**
     * StartupModificationService constructor.
     */
    public function __construct(private ConnectionInterface $connection, private VariableValidatorService $validatorService)
    {
    }

    /**
     * Process startup modification for a server.
     *
     * @throws \Throwable
     */
    public function handle(Server $server, array $data): Server
    {
        return $this->connection->transaction(function () use ($server, $data) {
            if (!empty($data['environment'])) {
                $egg = $this->isUserLevel(User::USER_LEVEL_ADMIN) ? ($data['egg_id'] ?? $server->egg_id) : $server->egg_id;

                // Variables that were not provided keep their current value (or the egg default
                // when the server has no value stored or is switching eggs), rather than being
                // overwritten with an empty value.
                $environment = $data['environment'];
                $current = (int) $egg === $server->egg_id
                    ? $server->variables->pluck('server_value', 'env_variable')
                    : collect();

                foreach (EggVariable::query()->where('egg_id', $egg)->get() as $variable) {
                    if (!array_key_exists($variable->env_variable, $environment)) {
                        $environment[$variable->env_variable] = $current->get($variable->env_variable) ?? $variable->default_value;
                    }
                }

                $results = $this->validatorService
                    ->setUserLevel($this->getUserLevel())
                    ->handle($egg, $environment);

                foreach ($results as $result) {
                    ServerVariable::query()->updateOrCreate(
                        [
                            'server_id' => $server->id,
                            'variable_id' => $result->id,
                        ],
                        ['variable_value' => $result->value ?? '']
                    );
                }
            }

            if ($this->isUserLevel(User::USER_LEVEL_ADMIN)) {
                $this->updateAdministrativeSettings($data, $server);
            }

            // Calling ->refresh() rather than ->fresh() here causes it to return the
            // variables as triplicates for some reason? Not entirely sure, should dig
            // in more to figure it out, but luckily we have a test case covering this
            // specific call so we can be assured we're not breaking it _here_ at least.
            //
            // TODO(dane): this seems like a red-flag for the code powering the relationship
            //  that should be looked into more.
            return $server->fresh();
        });
    }

    /**
     * Update certain administrative settings for a server in the DB.
     */
    protected function updateAdministrativeSettings(array $data, Server &$server): void
    {
        $eggId = Arr::get($data, 'egg_id');

        if (is_digit($eggId) && $server->egg_id !== (int) $eggId) {
            /** @var Egg $egg */
            $egg = Egg::query()->findOrFail($data['egg_id']);

            $server = $server->forceFill([
                'egg_id' => $egg->id,
                'nest_id' => $egg->nest_id,
            ]);
        }

        $server->fill([
            'startup' => $data['startup'] ?? $server->startup,
            'skip_scripts' => $data['skip_scripts'] ?? isset($data['skip_scripts']),
            'image' => $data['image'] ?? $server->image,
        ])->save();
    }
}
