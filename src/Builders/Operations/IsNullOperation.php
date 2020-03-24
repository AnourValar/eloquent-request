<?php

namespace AnourValar\EloquentRequest\Builders\Operations;

use AnourValar\EloquentRequest\Helpers\Fail;

class IsNullOperation implements OperationInterface
{
    /**
     * {@inheritDoc}
     * @see \AnourValar\EloquentRequest\Builders\Operations\OperationInterface::cast()
     */
    public function cast() : bool
    {
        return false;
    }

    /**
     * {@inheritDoc}
     * @see \AnourValar\EloquentRequest\Builders\Operations\OperationInterface::passes()
     */
    public function passes($value) : bool
    {
        return true;
    }

    /**
     * {@inheritDoc}
     * @see \AnourValar\EloquentRequest\Builders\Operations\OperationInterface::validate()
     */
    public function validate($value, \Closure $fail) : ?Fail
    {
        return null;
    }

    /**
     * {@inheritDoc}
     * @see \AnourValar\EloquentRequest\Builders\Operations\OperationInterface::apply()
     */
    public function apply(\Illuminate\Database\Eloquent\Builder &$query, string $field, $value) : void
    {
        $range = [];

        foreach ((array)$value as $item) {
            if ($item) {
                $range[1] = 1;
            } else {
                $range[0] = 0;
            }
        }

        if (count($range) != 1) {
            return;
        }

        if (isset($range[1])) {
            $query->whereNull($field);
        } else {
            $query->whereNotNull($field);
        }
    }
}
