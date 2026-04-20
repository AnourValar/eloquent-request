<?php

namespace AnourValar\EloquentRequest\Builders\Operations;

use Illuminate\Database\Query\Expression;

class LeOperation extends LtOperation
{
    /**
     * {@inheritDoc}
     * @see \AnourValar\EloquentRequest\Builders\Operations\OperationInterface::apply()
     */
    public function apply(\Illuminate\Database\Eloquent\Builder &$query, string|Expression $field, $value, array $options): void
    {
        $value = $this->canonizeValue($value, '<=');

        $query->where($field, '<=', $value);
    }
}
