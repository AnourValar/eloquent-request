<?php

namespace AnourValar\EloquentRequest\Builders\Operations;

class GeOperation extends LtOperation
{
    /**
     * {@inheritDoc}
     * @see \AnourValar\EloquentRequest\Builders\Operations\OperationInterface::apply()
     */
    public function apply(\Illuminate\Database\Eloquent\Builder &$query, string $field, $value, array $options): void
    {
        $value = $this->canonizeValue($value, '>=');

        $query->where($field, '>=', $value);
    }
}
