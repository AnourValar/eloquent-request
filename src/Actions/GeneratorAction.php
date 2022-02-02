<?php

namespace AnourValar\EloquentRequest\Actions;

use Illuminate\Database\Eloquent\Builder;

class GeneratorAction implements ActionInterface
{
    /**
     * @var string
     */
    const OPTION_APPLY_CHUNK = 'action.generator.apply_chunk';

    /**
     * @var string
     */
    const OPTION_APPLY_CHUNK_ORDER_BY = 'action.generator.apply_chunk_order_by';

    /**
     * @var string
     */
    const OPTION_LIMIT = 'action.generator.limit';

    /**
     * {@inheritDoc}
     * @see \AnourValar\EloquentRequest\Actions\ActionInterface::passes()
     */
    public function passes(array $profile, array $request, array $config): bool
    {
        return isset($profile['options'][self::OPTION_APPLY_CHUNK]);
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
     * @see \AnourValar\EloquentRequest\Actions\ActionInterface::action()
     */
    public function action(Builder &$query, array $profile, array $request, array $config, \Closure $fail)
    {
        if (isset($profile['options'][self::OPTION_APPLY_CHUNK_ORDER_BY])) {
            return $this->createGeneratorById(
                $profile['options'][self::OPTION_APPLY_CHUNK],
                $profile['options'][self::OPTION_APPLY_CHUNK_ORDER_BY],
                $query,
                ($profile['options'][self::OPTION_LIMIT] ?? null)
            );
        }

        return $this->createGenerator(
            $profile['options'][self::OPTION_APPLY_CHUNK],
            $query,
            ($profile['options'][self::OPTION_LIMIT] ?? null)
        );
    }

    /**
     * Create iterable generator (similar to chunk)
     *
     * @param integer $chunkSize
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @throws \LogicException
     * @return \Closure
     */
    protected function createGenerator(int $chunkSize, Builder &$query, int $limit = null): \Closure
    {
        return function () use ($chunkSize, $query, $limit)
        {
            if (empty($query->getQuery()->orders) && empty($query->getQuery()->unionOrders)) {
                throw new \LogicException('You must specify an orderBy clause when using this function.');
            }

            $page = 0;

            do {
                $page++;

                $collection = $query->forPage($page, $chunkSize)->get();
                foreach ($collection as $result) {
                    yield $result;

                    if ($limit) {
                        $limit--;
                        if (! $limit) {
                            break 2;
                        }
                    }
                }
            } while ($collection->count() == $chunkSize);
        };
    }

    /**
     * Create iterable generator (similar to chunkById)
     *
     * @param integer $chunkSize
     * @param array $chunkOrder
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @throws \LogicException
     * @return \Closure
     */
    protected function createGeneratorById(int $chunkSize, array $chunkOrder, Builder &$query, int $limit = null): \Closure
    {
        return function () use ($chunkSize, $chunkOrder, $query, $limit)
        {
            $orderValue = null;

            $orderKey = array_keys($chunkOrder)[0];
            $orderDestinition = mb_strtoupper(array_values($chunkOrder)[0]);

            $orderAttribute = explode('.', $orderKey);
            $orderAttribute = array_pop($orderAttribute);

            do {
                if ($orderDestinition == 'ASC') {
                    $collection = (clone $query)->forPageAfterId($chunkSize, $orderValue, $orderKey)->get();
                } else {
                    $collection = (clone $query)->forPageBeforeId($chunkSize, $orderValue, $orderKey)->get();
                }

                if ($collection->count()) {
                    $orderValue = $collection->last()->$orderAttribute;
                }

                foreach ($collection as $result) {
                    yield $result;

                    if ($limit) {
                        $limit--;
                        if (! $limit) {
                            break 2;
                        }
                    }
                }
            } while ($collection->count() == $chunkSize);
        };
    }
}
