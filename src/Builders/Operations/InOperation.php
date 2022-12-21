<?php

namespace AnourValar\EloquentRequest\Builders\Operations;

class InOperation implements OperationInterface
{
    /**
     * @var int
     */
    protected const MAX_LENGTH = 100;

    /**
     * @var int
     */
    protected const MAX_COUNT = 1000;

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
        return isset($value);
    }

    /**
     * {@inheritDoc}
     * @see \AnourValar\EloquentRequest\Builders\Operations\OperationInterface::validate()
     */
    public function validate($value, \Closure $fail): void
    {
        if (! is_array($value)) {
            $fail('eloquent-request::validation.list');
        }

        if (count($value) > static::MAX_COUNT) {
            $fail('eloquent-request::validation.list');
        }

        foreach ($value as $item) {
            if (! is_scalar($item) && ! is_null($item)) {
                $fail('eloquent-request::validation.list');
            }

            if (mb_strlen($item) > static::MAX_LENGTH) {
                $fail('eloquent-request::validation.list');
            }
        }
    }

    /**
     * {@inheritDoc}
     * @see \AnourValar\EloquentRequest\Builders\Operations\OperationInterface::apply()
     */
    public function apply(\Illuminate\Database\Eloquent\Builder &$query, string $field, $value, array $options): void
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
            $query->where(function ($query) use ($field, $value) {
                $query
                    ->whereNull($field)
                    ->orWhereIn($field, $value);
            });
        } else {
            $query->whereIn($field, $value);
        }
    }
}
