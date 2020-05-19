<?php

namespace AnourValar\EloquentRequest\Builders\Operations;

class JsonInOperation extends InOperation
{
    /**
     * @var integer
     */
    protected const MAX_LENGTH = 100;

    /**
     * @var integer
     */
    protected const MAX_COUNT = 100;

    /**
     * {@inheritDoc}
     * @see \AnourValar\EloquentRequest\Builders\Operations\OperationInterface::apply()
     */
    public function apply(\Illuminate\Database\Eloquent\Builder &$query, string $field, $value) : void
    {
        $query->where(function ($query) use ($field, $value)
        {
            foreach ($value as $item) {
                $query->orWhereJsonContains($field, $item);
            }
        });
    }
}
