<?php

namespace AnourValar\EloquentRequest\Builders\Operations;

class NotEqOperation extends EqOperation
{
    /**
     * {@inheritDoc}
     * @see \AnourValar\EloquentRequest\Builders\Operations\OperationInterface::apply()
     */
    public function apply(\Illuminate\Database\Eloquent\Builder &$query, string $field, $value, array $options): void
    {
        if ($value === '' || is_null($value) || $value === 0 || $value === '0') {
            $query
                ->when(! is_null($value), function ($query) use ($field, $value) {
                    $query->where($field, '!=', $value);
                })
                ->whereNotNull($field);
        } else {
            $query->where($field, '!=', $value);
        }
    }
}
