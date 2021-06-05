<?php

namespace AnourValar\EloquentRequest\Builders\Operations;

class NotInOperation extends InOperation
{
    /**
     * {@inheritDoc}
     * @see \AnourValar\EloquentRequest\Builders\Operations\OperationInterface::apply()
     */
    public function apply(\Illuminate\Database\Eloquent\Builder &$query, string $field, $value): void
    {
        $nullable = false;
        foreach ($value as $key => $item) {
            if ($item === '' || is_null($item) || $item === 0 || $item === '0') {
                $nullable = true;

                if (is_null($item)) {
                    unset($value[$key]);
                }
            }
        }

        if ($nullable) {
            $query
                ->whereNotIn($field, $value)
                ->whereNotNull($field);
        } else {
            $query->whereNotIn($field, $value);
        }
    }
}
