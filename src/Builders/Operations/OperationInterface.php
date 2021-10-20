<?php

namespace AnourValar\EloquentRequest\Builders\Operations;

interface OperationInterface
{
    /**
     * Casts filter value
     *
     * @return boolean
     */
    public function cast(): bool;

    /**
     * Pass filter (or ignore it)
     *
     * @param mixed $value
     * @return boolean
     */
    public function passes($value): bool;

    /**
     * Validation
     *
     * @param mixed $value
     * @param \Closure $fail
     * @throws \AnourValar\EloquentRequest\Helpers\FailException
     * @return void
     */
    public function validate($value, \Closure $fail): void;

    /**
     * Apply filter
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $field
     * @param mixed $value
     * @param array $options
     * @return void
     */
    public function apply(\Illuminate\Database\Eloquent\Builder &$query, string $field, $value, array $options): void;
}
