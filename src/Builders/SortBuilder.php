<?php

namespace AnourValar\EloquentRequest\Builders;

use \Illuminate\Validation\Validator;

class SortBuilder
{
    /**
     * Sort
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $profile
     * @param array $request
     * @param array $options
     * @param \Illuminate\Validation\Validator $validator
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function build(
        \Illuminate\Database\Eloquent\Builder $query,
        array $profile,
        array $request,
        array $options,
        Validator &$validator
    ) {
        foreach ((array)optional($request)[$options['sort_key']] as $field => $value) {
            static::applySort($query, $profile, $field, $value, $options, $validator);
        }

        return $query;
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $profile
     * @param string $field
     * @param mixed $value
     * @param array $options
     * @return void
     */
    protected static function applySort(
        \Illuminate\Database\Eloquent\Builder &$query,
        array $profile,
        $field,
        $value,
        array $options,
        Validator &$validator
    ) {
        $key = $options['sort_key'];

        // Availables options
        $values = ['ASC', 'DESC'];

        // Described in profile?
        if (!isset($profile[$key]) || !in_array($field, $profile[$key])) {
            $validator->after(function ($validator) use ($field, $key)
            {
                $validator->errors()->add(
                    $key . '.' . $field,
                    trans(
                        'eloquent-request::validation.sort_not_supported',
                        ['attribute' => ($validator->customAttributes[$field] ?? $field)]
                    )
                );
            });

            return;
        }

        // Correct sort?
        if (is_string($value)) {
            $value = mb_strtoupper($value);
        }

        if (! in_array($value, $values)) {
            $validator->after(function ($validator) use ($field, $key)
            {
                $validator->errors()->add(
                    $key . '.' . $field,
                    trans(
                        'eloquent-request::validation.sort_not_exists',
                        ['attribute' => ($validator->customAttributes[$field] ?? $field)]
                    )
                );
            });

            return;
        }

        $query->orderBy($field, $value);
    }
}
