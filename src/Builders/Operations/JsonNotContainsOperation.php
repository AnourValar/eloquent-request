<?php

namespace AnourValar\EloquentRequest\Builders\Operations;

use Illuminate\Database\Query\Expression;

class JsonNotContainsOperation extends JsonContainsOperation
{
    /**
     * {@inheritDoc}
     * @see \AnourValar\EloquentRequest\Builders\Operations\OperationInterface::apply()
     */
    public function apply(\Illuminate\Database\Eloquent\Builder &$query, string|Expression $field, $value, array $options): void
    {
        $query->whereJsonDoesntContain($field, $value);
    }
}
