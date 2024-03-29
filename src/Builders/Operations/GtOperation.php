<?php

namespace AnourValar\EloquentRequest\Builders\Operations;

class GtOperation extends LtOperation
{
    /**
     * {@inheritDoc}
     * @see \AnourValar\EloquentRequest\Builders\Operations\OperationInterface::apply()
     */
    public function apply(\Illuminate\Database\Eloquent\Builder &$query, string $field, $value, array $options): void
    {
        $value = $this->canonizeValue($value, '>');

        $query->where($field, '>', $value);
    }
}
