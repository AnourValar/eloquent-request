<?php

namespace AnourValar\EloquentRequest\Builders\Operations;

class JsonNotContainsOperation extends JsonInOperation
{
    /**
     * {@inheritDoc}
     * @see \AnourValar\EloquentRequest\Builders\Operations\OperationInterface::validate()
     */
    public function validate($value, \Closure $fail): void
    {
        if (is_array($value)) {
            parent::validate($value, $fail);
        } elseif (!(is_scalar($value) || is_null($value)) || mb_strlen($value) > static::MAX_LENGTH) {
            $fail('eloquent-request::validation.list');
        }
    }

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
