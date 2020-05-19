<?php

namespace AnourValar\EloquentRequest\Builders\Operations;

class JsonNotInOperation extends JsonInOperation
{
    /**
     * {@inheritDoc}
     * @see \AnourValar\EloquentRequest\Builders\Operations\OperationInterface::apply()
     */
    public function apply(\Illuminate\Database\Eloquent\Builder &$query, string $field, $value) : void
    {
        $query->where(function ($query) use ($field, $value)
        {
            foreach ($value as $item) {
                $query->whereJsonDoesntContain($field, $item);
            }
        });
    }
}
