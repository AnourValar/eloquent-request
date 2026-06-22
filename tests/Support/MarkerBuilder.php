<?php

namespace AnourValar\EloquentRequest\Tests\Support;

use AnourValar\EloquentRequest\Builders\BuilderInterface;
use AnourValar\EloquentRequest\Validators\ValidatorInterface;
use Illuminate\Database\Eloquent\Builder;

class MarkerBuilder implements BuilderInterface
{
    /**
     * Makes the instance "callable" (required by Service::extendBuilders signature)
     *
     * @return void
     */
    public function __invoke(): void
    {
        //
    }

    /**
     * Applies a hard constraint and reports itself in the build request
     *
     * {@inheritDoc}
     * @see \AnourValar\EloquentRequest\Builders\BuilderInterface::build()
     */
    public function build(Builder &$query, array $profile, array $request, array $config, ValidatorInterface &$validator): array
    {
        $query->where('status', '=', 'active');

        return ['marker' => true];
    }
}
