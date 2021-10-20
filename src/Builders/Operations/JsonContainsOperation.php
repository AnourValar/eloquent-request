<?php

namespace AnourValar\EloquentRequest\Builders\Operations;

class JsonContainsOperation extends JsonInOperation
{
    /**
     * {@inheritDoc}
     * @see \AnourValar\EloquentRequest\Builders\Operations\OperationInterface::apply()
     */
    public function apply(\Illuminate\Database\Eloquent\Builder &$query, string $field, $value, array $options): void
    {
        $this->convertOperands($field, $value, $options);

        $query->whereJsonContains($field, $value);
    }
}
