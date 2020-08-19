<?php

namespace AnourValar\EloquentRequest\Builders\Operations;

class LtOperation implements OperationInterface
{
    /**
     * @var integer
     */
    protected const MAX_LENGTH = 30;

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
        if (is_null($value) || (is_scalar($value) && !mb_strlen($value))) {
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
        if (is_scalar($value) && mb_strlen($value) <= static::MAX_LENGTH) {
            return;
        }

        $fail('eloquent-request::validation.scalar');
    }

    /**
     * {@inheritDoc}
     * @see \AnourValar\EloquentRequest\Builders\Operations\OperationInterface::apply()
     */
    public function apply(\Illuminate\Database\Eloquent\Builder &$query, string $field, $value): void
    {
        $value = $this->canonizeValue($value, '<');

        $query->where($field, '<', $value);
    }

    /**
     * @param mixed $value
     * @param string $direction
     * @return mixed
     */
    protected function canonizeValue($value, string $direction)
    {
        preg_match('|^\d{2,4}([\/\.\-])\d{2,4}\1\d{2,4}(.*)$|', $value, $result);

        if (! $result) {
            return $value;
        }

        // datetime
        if (stripos($result[2], ':')) {
            if ($direction == '<') {
                return date('Y-m-d H:i:00', strtotime($value));
            }
            if ($direction == '<=') {
                return date('Y-m-d H:i:59', strtotime($value));
            }

            if ($direction == '>') {
                return date('Y-m-d H:i:59', strtotime($value));
            }
            if ($direction == '>=') {
                return date('Y-m-d H:i:00', strtotime($value));
            }
        }

        // date
        if ($direction == '<') {
            return date('Y-m-d 00:00:00', strtotime($value));
        }
        if ($direction == '<=') {
            return date('Y-m-d 23:59:59', strtotime($value));
        }

        if ($direction == '>') {
            return date('Y-m-d 23:59:59', strtotime($value));
        }
        if ($direction == '>=') {
            return date('Y-m-d 00:00:00', strtotime($value));
        }
    }
}
