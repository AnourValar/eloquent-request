<?php

namespace AnourValar\EloquentRequest\Builders\Operations;

class SearchOperation extends LikeOperation
{
    /**
     * {@inheritDoc}
     * @see \AnourValar\EloquentRequest\Builders\Operations\LikeOperation::validate()
     */
    public function validate($value, \Closure $fail): void
    {
        if (is_string($value)) {
            $value = trim($value);
        }

        parent::validate($value, $fail);
    }

    /**
     * {@inheritDoc}
     * @see \AnourValar\EloquentRequest\Builders\Operations\OperationInterface::apply()
     */
    public function apply(\Illuminate\Database\Eloquent\Builder &$query, string $field, $value, array $options): void
    {
        $query->where(function ($query) use ($field, $value)
        {
            $fullValue = str_replace(' ', '', $value);

            $query
                ->when(mb_strlen($fullValue) >= static::MIN_LENGTH, function ($query) use ($field, $fullValue)
                {
                    $query->where($field, 'LIKE', $this->canonizeValue($fullValue));
                })
                ->orWhere($field, 'LIKE', $this->canonizeValue($value));
        });
    }

    /**
     * {@inheritDoc}
     * @see \AnourValar\EloquentRequest\Builders\Operations\LikeOperation::canonizeValue()
     */
    protected function canonizeValue($value): string
    {
        return \EloquentRequestSearch::prepare($value);
    }
}
