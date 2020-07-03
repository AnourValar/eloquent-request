<?php

namespace AnourValar\EloquentRequest\Actions;

use Illuminate\Database\Eloquent\Builder;

class GeneratorAction implements ActionInterface
{
    /**
     * @var string
     */
    const OPTION_APPLY_SIZE = 'action.generator.apply_size';

    /**
     * @var string
     */
    const OPTION_LIMIT = 'action.generator.limit';

    /**
     * {@inheritDoc}
     * @see \AnourValar\EloquentRequest\Actions\ActionInterface::passes()
     */
    public function passes(array $profile, array $request, array $config) : bool
    {
        return isset($profile['options'][self::OPTION_APPLY_SIZE]);
    }

    /**
     * {@inheritDoc}
     * @see \AnourValar\EloquentRequest\Actions\ActionInterface::validate()
     */
    public function validate(array $profile, array $request, array $config, \Closure $fail) : void
    {

    }

    /**
     * {@inheritDoc}
     * @see \AnourValar\EloquentRequest\Actions\ActionInterface::action()
     */
    public function action(Builder &$query, array $profile, array $request, array $config, \Closure $fail)
    {
        return $this->createGenerator(
            $profile['options'][self::OPTION_APPLY_SIZE],
            $query,
            ($profile['options'][self::OPTION_LIMIT] ?? null)
        );
    }

    /**
     * Create iterable generator
     *
     * @param integer $chunkSize
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @throws \LogicException
     * @return \Closure
     */
    public function createGenerator(int $chunkSize, Builder &$query, int $limit = null) : \Closure
    {
        return function () use ($chunkSize, $query, $limit)
        {
            static $results;

            if (empty($query->getQuery()->orders) && empty($query->getQuery()->unionOrders)) {
                throw new \LogicException('You must specify an orderBy clause when using this function.');
            }

            $page = 0;

            do {
                $page++;

                if (! isset($results[$page])) {
                    $results = [$page => $query->forPage($page, $chunkSize)->get()];
                }

                foreach ($results[$page] as $result) {
                    yield $result;

                    if ($limit) {
                        $limit--;
                        if (! $limit) {
                            break 2;
                        }
                    }
                }
            } while ($results[$page]->count() == $chunkSize);
        };
    }
}
