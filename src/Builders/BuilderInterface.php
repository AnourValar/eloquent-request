<?php

namespace AnourValar\EloquentRequest\Builders;

use Illuminate\Database\Eloquent\Builder;
use AnourValar\EloquentRequest\Validators\ValidatorInterface;

interface BuilderInterface
{
    /**
     * Query builder filling
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $profile
     * @param array $request
     * @param array $config
     * @param \AnourValar\EloquentRequest\Validators\ValidatorInterface $validator
     * @return void
     */
    public function build(Builder &$query, array $profile, array $request, array $config, ValidatorInterface &$validator): void;
}
