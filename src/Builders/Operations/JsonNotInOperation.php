<?php

namespace AnourValar\EloquentRequest\Builders\Operations;

use Illuminate\Database\Query\Expression;

class JsonNotInOperation extends JsonInOperation
{
    /**
     * {@inheritDoc}
     * @see \AnourValar\EloquentRequest\Builders\Operations\OperationInterface::apply()
     */
    public function apply(\Illuminate\Database\Eloquent\Builder &$query, string|Expression $field, $value, array $options): void
    {
        $query->where(function ($query) use ($field, $value) {
            foreach ($value as $item) { // array_unique...
                $query->whereJsonDoesntContain($field, $item);
            }
        });
    }
}
