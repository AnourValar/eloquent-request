<?php

namespace AnourValar\EloquentRequest\Actions;

use AnourValar\EloquentRequest\Helpers\Fail;
use Illuminate\Database\Eloquent\Builder;

class DumpAction implements ActionInterface
{
    /**
     * @var string
     */
    const OPTION_TURN_ON = 'action.dump.turn_on';

    /**
     * {@inheritDoc}
     * @see \AnourValar\EloquentRequest\Actions\ActionInterface::passes()
     */
    public function passes(array $profile, array $request, array $config) : bool
    {
        return in_array(self::OPTION_TURN_ON, $profile['options']);
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
        dd( $query->toSql(), $query->getBindings() );
    }
}
