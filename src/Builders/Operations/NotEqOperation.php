<?php

namespace AnourValar\EloquentRequest\Builders\Operations;

use Illuminate\Database\Query\Expression;

class NotEqOperation extends EqOperation
{
    /**
     * {@inheritDoc}
     * @see \AnourValar\EloquentRequest\Builders\Operations\OperationInterface::apply()
     */
    public function apply(\Illuminate\Database\Eloquent\Builder &$query, string|Expression $field, $value, array $options): void
    {
        if (is_null($value)) {
            $query->whereNotNull($field);
        } else {
            $query->where($field, '!=', $value);
        }
    }
}
