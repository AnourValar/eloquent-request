<?php

namespace AnourValar\EloquentRequest\Builders\Operations;

class InOperation
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
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $field
     * @param mixed $value
     * @return void
     */
    public static function in(\Illuminate\Database\Eloquent\Builder $query, $field, $value)
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
                        $query->where($field, '=', '')->orWhereNull($field);
                    })
                    ->orWhereIn($field, $value);
            });
        } else {
            $query->whereIn($field, $value);
        }
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $field
     * @param mixed $value
     * @return void
     */
    public static function notIn(\Illuminate\Database\Eloquent\Builder $query, $field, $value)
    {
        $nullable = false;
        foreach ($value as $key => $item) {
            if ($item === '' || is_null($item)) {
                $nullable = true;
                unset($value[$key]);
            }
        }

        if ($nullable) {
            $query
                ->whereNotIn($field, $value)
                ->where($field, '!=', '')
                ->whereNotNull($field);
        } else {
            $query->whereNotIn($field, $value);
        }
    }

    /**
     * @param mixed $value
     * @return boolean
     */
    public static function validate($value)
    {
        if (!is_array($value)) {
            return false;
        }

        if (count($value) > static::MAX_COUNT) {
            return false;
        }

        foreach ($value as $item) {
            if (!is_scalar($item) && !is_null($item)) {
                return false;
            }

            if (mb_strlen($item) > static::MAX_LENGTH) {
                return false;
            }
        }

        return true;
    }
}
