<?php

namespace AnourValar\EloquentRequest\Builders\Operations;

class MixOperation
{
    /**
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $field
     * @param mixed $value
     * @return void
     */
    public static function isNull(\Illuminate\Database\Eloquent\Builder $query, $field, $value)
    {
        $range = [];

        foreach ((array)$value as $item) {
            if ($item) {
                $range[1] = 1;
            } else {
                $range[0] = 0;
            }
        }

        if (count($range) != 1) {
            return;
        }

        if (isset($range[1])) {
            $query->whereNull($field);
        } else {
            $query->whereNotNull($field);
        }
    }
}
