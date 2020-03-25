<?php

namespace AnourValar\EloquentRequest\Actions;

use AnourValar\EloquentRequest\Helpers\Fail;
use Illuminate\Database\Eloquent\Builder;

class GetAction implements ActionInterface
{
    /**
     * @var string
     */
    const OPTION_APPLY = 'action.get.apply';

    /**
     * @var string
     */
    const OPTION_LIMIT = 'action.get.limit';

    /**
     * {@inheritDoc}
     * @see \AnourValar\EloquentRequest\Actions\ActionInterface::passes()
     */
    public function passes(array $profile, array $request, array $config) : bool
    {
        return in_array(self::OPTION_APPLY, $profile['options']);
    }

    /**
     * {@inheritDoc}
     * @see \AnourValar\EloquentRequest\Actions\ActionInterface::validate()
     */
    public function validate(array $profile, array $request, array $config, \Closure $fail) : ?Fail
    {
        return null;
    }

    /**
     * {@inheritDoc}
     * @see \AnourValar\EloquentRequest\Actions\ActionInterface::action()
     */
    public function action(Builder &$query, array $profile, array $request, array $config, \Closure $fail)
    {
        if (isset($profile['options'][self::OPTION_LIMIT])) {
            $query->limit($profile['options'][self::OPTION_LIMIT]);
        }

        return $query->get();
    }
}
