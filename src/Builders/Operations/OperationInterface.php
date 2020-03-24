<?php

namespace AnourValar\EloquentRequest\Builders\Operations;

use AnourValar\EloquentRequest\Helpers\Fail;

interface OperationInterface
{
    /**
     * Casts filter value
     *
     * @return bool
     */
    public function cast() : bool;

    /**
     * Pass filter (or ignore it)
     *
     * @param mixed $value
     * @return boolean
     */
    public function passes($value) : bool;

    /**
     * Validation
     *
     * @param mixed $value
     * @param \Closure $fail
     * @return \Closure|NULL
     */
    public function validate($value, \Closure $fail) : ?Fail;

    /**
     * Apply filter
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $field
     * @param mixed $value
     * @return void
     */
    public function apply(\Illuminate\Database\Eloquent\Builder &$query, string $field, $value) : void;
}
