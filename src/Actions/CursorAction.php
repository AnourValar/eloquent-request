<?php

namespace AnourValar\EloquentRequest\Actions;

use Illuminate\Database\Eloquent\Builder;

class CursorAction implements ActionInterface
{
    /**
     * @var string
     */
    public const OPTION_APPLY = 'action.cursor.apply';

    /**
     * @var string
     */
    public const OPTION_LIMIT = 'action.cursor.limit';

    /**
     * {@inheritDoc}
     * @see \AnourValar\EloquentRequest\Actions\ActionInterface::passes()
     */
    public function passes(array $profile, array $request, array $config): bool
    {
        return in_array(self::OPTION_APPLY, $profile['options']);
    }

    /**
     * {@inheritDoc}
     * @see \AnourValar\EloquentRequest\Actions\ActionInterface::validate()
     */
    public function validate(array $profile, array $request, array $config, \Closure $fail): void
    {

    }

    /**
     * {@inheritDoc}
     * @see \AnourValar\EloquentRequest\Actions\ActionInterface::requestParameters()
     */
    public function requestParameters(array $profile, array $request, array $config): array
    {
        return [];
    }

    /**
     * {@inheritDoc}
     * @see \AnourValar\EloquentRequest\Actions\ActionInterface::action()
     */
    public function action(Builder &$query, array $profile, array $requestParameters, array $config, \Closure $fail)
    {
        if (isset($profile['options'][self::OPTION_LIMIT])) {
            $query->limit($profile['options'][self::OPTION_LIMIT]);
        }

        return $query->cursor();
    }
}
