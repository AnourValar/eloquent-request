<?php

namespace AnourValar\EloquentRequest\Builders;

use AnourValar\EloquentRequest\Validators\ValidatorInterface;
use Illuminate\Database\Eloquent\Builder;

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
