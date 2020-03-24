<?php

namespace AnourValar\EloquentRequest\Actions;

use AnourValar\EloquentRequest\Helpers\Fail;
use Illuminate\Database\Eloquent\Builder;

interface ActionInterface
{
    /**
     * Pass action (or skip it)
     *
     * @param array $profile
     * @param array $request
     * @param array $config
     * @return boolean
     */
    public function passes(array $profile, array $request, array $config) : bool;

    /**
     * Validation
     *
     * @param array $profile
     * @param array $request
     * @param array $config
     * @param \Closure $fail
     * @return \AnourValar\EloquentRequest\Helpers\Fail|NULL
     */
    public function validate(array $profile, array $request, array $config, \Closure $fail) : ?Fail;

    /**
     * Get collection
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $profile
     * @param array $request
     * @param array $config
     * @param \Closure $fail
     * @return mixed
     */
    public function action(Builder &$query, array $profile, array $request, array $config, \Closure $fail);
}
