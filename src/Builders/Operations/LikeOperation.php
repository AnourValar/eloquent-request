<?php

namespace AnourValar\EloquentRequest\Builders\Operations;

class LikeOperation
{
    /**
     * @var integer
     */
    protected const MIN_LENGTH = 3;

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
    public static function like(\Illuminate\Database\Eloquent\Builder $query, $field, $value)
    {
        $value = static::canonizeValue($value);
        $query->where($field, 'LIKE', "%$value%");
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $field
     * @param mixed $value
     * @return void
     */
    public static function notLike(\Illuminate\Database\Eloquent\Builder $query, $field, $value)
    {
        $value = static::canonizeValue($value);
        $query->where($field, 'NOT LIKE', "%$value%");
    }

    /**
     * @param mixed $value
     * @return boolean
     */
    public static function validate($value)
    {
        $value = trim($value);

        return (is_scalar($value) && mb_strlen($value) >= static::MIN_LENGTH && mb_strlen($value) <= static::MAX_LENGTH);
    }

    /**
     * @param string $value
     * @return string
     */
    protected static function canonizeValue($value)
    {
        return addCslashes($value, '_%\\');
    }
}
