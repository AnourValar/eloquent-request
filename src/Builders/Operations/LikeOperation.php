<?php

namespace AnourValar\EloquentRequest\Builders\Operations;

class LikeOperation implements OperationInterface
{
    /**
     * @var int
     */
    protected const MIN_LENGTH = 3;

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
        if (is_null($value) || $value === '') {
            return false;
        }

        return true;
    }

    /**
     * {@inheritDoc}
     * @see \AnourValar\EloquentRequest\Builders\Operations\OperationInterface::validate()
     */
    public function validate($value, \Closure $fail): void
    {
        if (is_scalar($value) && mb_strlen($value) >= static::MIN_LENGTH && mb_strlen($value) <= static::MAX_LENGTH) {
            return;
        }

        $fail('eloquent-request::validation.length');
    }

    /**
     * {@inheritDoc}
     * @see \AnourValar\EloquentRequest\Builders\Operations\OperationInterface::apply()
     */
    public function apply(\Illuminate\Database\Eloquent\Builder &$query, string $field, $value, array $options): void
    {
        $value = $this->canonizeValue($value);

        $query->where($field, 'LIKE', $value);
    }

    /**
     * @param mixed $value
     * @return string
     */
    protected function canonizeValue($value): string
    {
        return '%'.addcslashes($value, '_%\\').'%';
    }
}
