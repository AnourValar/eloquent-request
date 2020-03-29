<?php

namespace AnourValar\EloquentRequest\Builders\Operations;

class NotLikeOperation extends LikeOperation
{
    /**
     * {@inheritDoc}
     * @see \AnourValar\EloquentRequest\Builders\Operations\OperationInterface::apply()
     */
    public function apply(\Illuminate\Database\Eloquent\Builder &$query, string $field, $value) : void
    {
        $value = $this->canonizeValue($value);

        $query->where($field, 'NOT LIKE', $value);
    }
}