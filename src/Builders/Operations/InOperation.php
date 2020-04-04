<?php

namespace AnourValar\EloquentRequest\Builders\Operations;

class InOperation implements OperationInterface
{
    /**
     * @var integer
     */
    protected const MAX_LENGTH = 100;

    /**
     * @var integer
     */
    protected const MAX_COUNT = 1000;

    /**
     * {@inheritDoc}
     * @see \AnourValar\EloquentRequest\Builders\Operations\OperationInterface::cast()
     */
    public function cast() : bool
    {
        return true;
    }

    /**
     * {@inheritDoc}
     * @see \AnourValar\EloquentRequest\Builders\Operations\OperationInterface::passes()
     */
    public function passes($value) : bool
    {
        return true;
    }

    /**
     * {@inheritDoc}
     * @see \AnourValar\EloquentRequest\Builders\Operations\OperationInterface::validate()
     */
    public function validate($value, \Closure $fail) : void
    {
        if (! is_array($value)) {
            $fail('eloquent-request::validation.list');
        }

        if (count($value) > static::MAX_COUNT) {
            $fail('eloquent-request::validation.list');
        }

        foreach ($value as $item) {
            if (!is_scalar($item) && !is_null($item)) {
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
    public function apply(\Illuminate\Database\Eloquent\Builder &$query, string $field, $value) : void
    {
        $nullable = false;
        foreach ($value as $key => $item) {
            if ($item === '' || is_null($item)) {
                $nullable = true;
                unset($value[$key]);
            }
        }

        if ($nullable) {
            $query->where(function ($query) use ($field, $value)
            {
                $query
                    ->where(function ($query) use ($field)
                    {
                        $query
                            ->where($field, '=', '')
                            ->orWhereNull($field);
                    })
                    ->orWhereIn($field, $value);
            });
        } else {
            $query->whereIn($field, $value);
        }
    }
}
