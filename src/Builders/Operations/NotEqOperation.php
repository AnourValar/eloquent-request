<?php

namespace AnourValar\EloquentRequest\Builders\Operations;

class NotEqOperation extends EqOperation
{
    /**
     * {@inheritDoc}
     * @see \AnourValar\EloquentRequest\Builders\Operations\OperationInterface::apply()
     */
    public function apply(\Illuminate\Database\Eloquent\Builder &$query, string $field, $value): void
    {
        if ($value === '' || is_null($value)) {
            $query
                ->where($field, '!=', '')
                ->whereNotNull($field);
        } else {
            $query->where($field, '!=', $value);
        }
    }
}
