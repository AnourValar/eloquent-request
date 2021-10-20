<?php

namespace AnourValar\EloquentRequest\Builders\Operations;

class JsonNotInOperation extends JsonInOperation
{
    /**
     * {@inheritDoc}
     * @see \AnourValar\EloquentRequest\Builders\Operations\OperationInterface::apply()
     */
    public function apply(\Illuminate\Database\Eloquent\Builder &$query, string $field, $value, array $options): void
    {
        $query->where(function ($query) use ($field, $value, $options)
        {
            $originalField = $field;
            foreach ($value as $item) {
                $field = $originalField;
                $this->convertOperands($field, $item, $options);

                $query->whereJsonDoesntContain($field, $item);
            }
        });
    }
}
