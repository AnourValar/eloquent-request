<?php

namespace AnourValar\EloquentRequest\Builders\Operations;

class LessGreaterOperation
{
    /**
     * @var integer
     */
    protected const MAX_LENGTH = 30;

    /**
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $field
     * @param mixed $value
     * @return void
     */
    public static function lt(\Illuminate\Database\Eloquent\Builder $query, $field, $value)
    {
        $dateType = self::getDateType($value);

        if ($dateType == 'date') {
            $value = date('Y-m-d 23:59:59', strtotime($value));
        } else if ($dateType == 'datetime') {
            $value = date('Y-m-d H:i:s', strtotime($value));
        }

        $query->where($field, '<', $value);
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $field
     * @param mixed $value
     * @return void
     */
    public static function le(\Illuminate\Database\Eloquent\Builder $query, $field, $value)
    {
        $dateType = self::getDateType($value);

        if ($dateType == 'date') {
            $value = date('Y-m-d 23:59:59', strtotime($value));
        } else if ($dateType == 'datetime') {
            $value = date('Y-m-d H:i:s', strtotime($value));
        }

        $query->where($field, '<=', $value);
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $field
     * @param mixed $value
     * @return void
     */
    public static function gt(\Illuminate\Database\Eloquent\Builder $query, $field, $value)
    {
        $dateType = self::getDateType($value);

        if ($dateType == 'date') {
            $value = date('Y-m-d 00:00:00', strtotime($value));
        } else if ($dateType == 'datetime') {
            $value = date('Y-m-d H:i:s', strtotime($value));
        }

        $query->where($field, '>', $value);
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $field
     * @param mixed $value
     * @return void
     */
    public static function ge(\Illuminate\Database\Eloquent\Builder $query, $field, $value)
    {
        $dateType = self::getDateType($value);

        if ($dateType == 'date') {
            $value = date('Y-m-d 00:00:00', strtotime($value));
        } else if ($dateType == 'datetime') {
            $value = date('Y-m-d H:i:s', strtotime($value));
        }

        $query->where($field, '>=', $value);
    }

    /**
     * @param mixed $value
     * @return boolean
     */
    private static function getDateType($value)
    {
        preg_match('|^\d{2,4}([\/\.\-])\d{2,4}\1\d{2,4}(.*)$|', $value, $result);

        if (!$result) {
            return false;
        }

        if (stripos($result[2], ':')) {
            return 'datetime';
        }

        return 'date';
    }

    /**
     * @param mixed $value
     * @return boolean
     */
    public static function validate($value)
    {
        return ( is_scalar($value) && mb_strlen($value) <= static::MAX_LENGTH );
    }
}
