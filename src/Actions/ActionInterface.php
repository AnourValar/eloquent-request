<?php

namespace AnourValar\EloquentRequest\Actions;

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
     * @throws \AnourValar\EloquentRequest\Helpers\FailException
     * @return void
     */
    public function validate(array $profile, array $request, array $config, \Closure $fail) : void;

    /**
     * Get collection
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $profile
     * @param array $request
     * @param array $config
     * @param \Closure $fail
     * @throws \AnourValar\EloquentRequest\Helpers\FailException
     * @return mixed
     */
    public function action(Builder &$query, array $profile, array $request, array $config, \Closure $fail);
}
