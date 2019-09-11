<?php

namespace AnourValar\EloquentRequest\Builders\Operations;

class EqOperation
{
    /**
     * @var integer
     */
    protected const MAX_LENGTH = 1000;

    /**
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $field
     * @param mixed $value
     * @return void
     */
    public static function eq(\Illuminate\Database\Eloquent\Builder $query, $field, $value)
    {
        if ($value === '' || is_null($value)) {
            $query->where(function ($query) use ($field)
            {
                $query->where($field, '=', '')->orWhereNull($field);
            });
        } else {
            $query->where($field, '=', $value);
        }
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $field
     * @param mixed $value
     * @return void
     */
    public static function notEq(\Illuminate\Database\Eloquent\Builder $query, $field, $value)
    {
        if ($value === '' || is_null($value)) {
            $query
                ->where($field, '!=', '')
                ->whereNotNull($field);
        } else {
            $query->where($field, '!=', $value);
        }
    }

    /**
     * @param mixed $value
     * @return boolean
     */
    public static function validate($value)
    {
        return (is_scalar($value) && mb_strlen($value) <= static::MAX_LENGTH) || is_null($value);
    }
}
