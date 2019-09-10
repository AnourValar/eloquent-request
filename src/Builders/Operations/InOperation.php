<?php

namespace AnourValar\EloquentRequest\Builders\Operations;

class InOperation
{
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

        foreach ($value as $item) {
            if (!is_scalar($item) && !is_null($item)) {
                return false;
            }
        }

        return true;
    }
}
