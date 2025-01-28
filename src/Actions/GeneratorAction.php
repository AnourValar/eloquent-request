<?php

namespace AnourValar\EloquentRequest\Actions;

use Illuminate\Database\Eloquent\Builder;

class GeneratorAction implements ActionInterface
{
    /**
     * @var string
     */
    public const OPTION_APPLY_CHUNK = 'action.generator.apply_chunk';

    /**
     * @var string
     */
    public const OPTION_APPLY_CHUNK_ORDER_BY = 'action.generator.apply_chunk_order_by';

    /**
     * @var string
     */
    public const OPTION_LIMIT = 'action.generator.limit';

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
     * Create iterable generator (lazy())
     *
     * @param int $chunkSize
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int|null $limit
     * @return \Closure
     */
    protected function createGenerator(int $chunkSize, Builder &$query, ?int $limit = null): \Closure
    {
        return function () use ($chunkSize, $query, $limit) {
            foreach ($query->lazy($chunkSize) as $item) {
                yield $item;

                if ($limit) {
                    $limit--;
                    if (! $limit) {
                        break;
                    }
                }
            }
        };
    }

    /**
     * Create iterable generator (lazyById())
     *
     * @param int $chunkSize
     * @param array $chunkOrder
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int|null $limit
     * @return \Closure
     */
    protected function createGeneratorById(int $chunkSize, array $chunkOrder, Builder &$query, ?int $limit = null): \Closure
    {
        return function () use ($chunkSize, $chunkOrder, $query, $limit) {
            $column = array_keys($chunkOrder)[0];
            $direction = mb_strtoupper($chunkOrder[$column]);

            $alias = explode('.', $column);
            $alias = array_pop($alias);

            if ($direction == 'ASC') {
                $method = 'lazyById';
            } else {
                $method = 'lazyByIdDesc';
            }

            foreach ($query->$method($chunkSize, $column, $alias) as $item) {
                yield $item;

                if ($limit) {
                    $limit--;
                    if (! $limit) {
                        break;
                    }
                }
            }
        };
    }
}
