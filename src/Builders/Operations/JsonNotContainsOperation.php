<?php

namespace AnourValar\EloquentRequest\Builders\Operations;

class JsonNotContainsOperation extends JsonContainsOperation
{
    /**
     * {@inheritDoc}
     * @see \AnourValar\EloquentRequest\Builders\Operations\OperationInterface::apply()
     */
    public function apply(\Illuminate\Database\Eloquent\Builder &$query, string $field, $value, array $options): void
    {
        $this->convertOperands($field, $value, $options);

        $query->whereJsonDoesntContain($field, $value);
    }
}
