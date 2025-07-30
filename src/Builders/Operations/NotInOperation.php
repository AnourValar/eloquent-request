<?php

namespace AnourValar\EloquentRequest\Builders\Operations;

class NotInOperation extends InOperation
{
    /**
     * {@inheritDoc}
     * @see \AnourValar\EloquentRequest\Builders\Operations\OperationInterface::apply()
     */
    public function apply(\Illuminate\Database\Eloquent\Builder &$query, string $field, $value, array $options): void
    {
        $nullable = false;
        foreach ($value as $key => $item) {
            if (is_null($item)) {
                $nullable = true;
                unset($value[$key]);
            }
        }
        //$value = array_unique($value);

        if ($nullable) {
            $query->whereNotNull($field);
            if ($value) {
                $query->whereNotIn($field, $value);
            }
        } else {
            $query->whereNotIn($field, $value);
        }
    }
}
