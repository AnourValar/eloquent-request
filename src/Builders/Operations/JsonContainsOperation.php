<?php

namespace AnourValar\EloquentRequest\Builders\Operations;

class JsonContainsOperation extends JsonInOperation
{
    /**
     * {@inheritDoc}
     * @see \AnourValar\EloquentRequest\Builders\Operations\OperationInterface::apply()
     */
    public function apply(\Illuminate\Database\Eloquent\Builder &$query, string $field, $value): void
    {
        $query->whereJsonContains($field, $value);
    }
}
