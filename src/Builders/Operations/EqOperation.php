<?php

namespace AnourValar\EloquentRequest\Builders\Operations;

class EqOperation implements OperationInterface
{
    /**
     * @var int
     */
    protected const MAX_LENGTH = 1000;

    /**
     * {@inheritDoc}
     * @see \AnourValar\EloquentRequest\Builders\Operations\OperationInterface::cast()
     */
    public function cast(): bool
    {
        return true;
    }

    /**
     * {@inheritDoc}
     * @see \AnourValar\EloquentRequest\Builders\Operations\OperationInterface::passes()
     */
    public function passes($value): bool
    {
        return true;
    }

    /**
     * {@inheritDoc}
     * @see \AnourValar\EloquentRequest\Builders\Operations\OperationInterface::validate()
     */
    public function validate($value, \Closure $fail): void
    {
        if ((is_scalar($value) && mb_strlen($value) <= static::MAX_LENGTH) || is_null($value)) {
            return;
        }

        $fail('eloquent-request::validation.scalar');
    }

    /**
     * {@inheritDoc}
     * @see \AnourValar\EloquentRequest\Builders\Operations\OperationInterface::apply()
     */
    public function apply(\Illuminate\Database\Eloquent\Builder &$query, string $field, $value, array $options): void
    {
        if (is_null($value)) {
            $query->whereNull($field);
        } else {
            $query->where($field, '=', $value);
        }
    }
}
