<?php

namespace AnourValar\EloquentRequest\Builders;

use \Illuminate\Validation\Validator;

class FilterAndScopeBuilder
{
    /**
     * @var string
     */
    const OPTION_SEPARATE = 'fasb-separate'; // prevents grouping for relations

    /**
     * Filters, Scopes
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
        $tasks = [];

        // Filter
        foreach ((array)optional($request)[$options['filter_key']] as $field => $values) {
            foreach ((array)$values as $operation => $value) {
                $task = static::getFilter($profile, $field, $operation, $value, $options, $validator);

                if ($task) {
                    $tasks[$task['key']][] = $task['value'];
                }
            }
        }

        // Scope
        foreach ((array)optional($request)[$options['scope_key']] as $scope => $value) {
            $task = static::getScope($profile, $scope, $value, $options, $validator);

            if ($task) {
                $tasks[$task['key']][] = $task['value'];
            }
        }

        // Apply tasks
        if ($tasks) {
            static::applyTasks($query, $tasks, in_array(self::OPTION_SEPARATE, $profile['options']));
        }

        return $query;
    }

    /**
     * @param array $profile
     * @param string $field
     * @param string $operation
     * @param mixed $value
     * @param array $options
     * @param \Illuminate\Validation\Validator $validator
     * @return array
     */
    protected static function getFilter(
        array $profile,
        string $field,
        string $operation,
        $value,
        array $options,
        Validator &$validator
    ) {
        $key = $options['filter_key'];

        // Field described in profile?
        if (!isset($profile[$key][$field])) {
            $validator->after(function ($validator) use ($field, $key)
            {
                $validator->errors()->add(
                    $key . '.' . $field,
                    trans(
                        'eloquent-request::validation.filter_not_supported',
                        ['attribute' => ($validator->customAttributes[$field] ?? $field)]
                    )
                );
            });

            return;
        }
        $profile[$key][$field] = (array)$profile[$key][$field];

        // Operation described in profile?
        if (!in_array($operation, $profile[$key][$field])) {
            $validator->after(function ($validator) use ($field, $key)
            {
                $validator->errors()->add(
                    $key . '.' . $field,
                    trans(
                        'eloquent-request::validation.operation_not_supported',
                        ['attribute' => ($validator->customAttributes[$field] ?? $field)]
                    )
                );
            });

            return;
        }

        // Operation exists?
        if (!isset($options['filter_operations'][$operation])) {
            $validator->after(function ($validator) use ($field, $key)
            {
                $validator->errors()->add(
                    $key . '.' . $field,
                    trans(
                        'eloquent-request::validation.operation_not_exists',
                        ['attribute' => ($validator->customAttributes[$field] ?? $field)]
                    )
                );
            });

            return;
        }

        $rules = $options['filter_operations'][$operation];

        if (isset($rules['validate']) && !$rules['validate']($value)) {
            if (isset($rules['error_message'])) {
                $validator->after(function ($validator) use ($field, $key, $rules)
                {
                    $validator->errors()->add(
                        $key . '.' . $field,
                        trans($rules['error_message'], ['attribute' => ($validator->customAttributes[$field] ?? $field)])
                    );
                });
            }

            return;
        }

        $path = explode('.', $field);
        $field = array_pop($path);
        $relation = implode('.', $path);

        return [
            'key' => $relation,
            'value' => ['field' => $field, 'apply' => $rules['apply'], 'value' => $value],
        ];
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $profile
     * @param string $scope
     * @param mixed $value
     * @param array $options
     * @return array
     */
    protected static function getScope(array $profile, string $scope, $value, array $options, Validator &$validator)
    {
        $key = $options['scope_key'];

        // Described in profile?
        if (!in_array($scope, ($profile[$key] ?? []))) {
            $validator->after(function ($validator) use ($scope, $key)
            {
                $validator->errors()->add(
                    $key . '.' . $scope,
                    trans('eloquent-request::validation.scope_not_supported', ['scope' => $scope])
                );
            });

            return;
        }

        $path = explode('.', $scope);
        $scope = array_pop($path);
        $relation = implode('.', $path);

        return [
            'key' => $relation,
            'value' => ['scope' => $scope, 'value' => $value],
        ];
    }

    /**
     * Builder: Filters & Scopes
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $tasks
     * @param boolean $separate
     * @return void
     */
    protected static function applyTasks(\Illuminate\Database\Eloquent\Builder &$query, array $tasks, bool $separate)
    {
        foreach ($tasks as $relation => $actions) {
            if ($relation && $separate) {
                foreach ($actions as $action) {
                    $query->whereHas($relation, function ($query) use ($action)
                    {
                        static::applyTask($query, $action);
                    });
                }
            } else if ($relation) {
                $query->whereHas($relation, function ($query) use ($actions)
                {
                    foreach ($actions as $action) {
                        static::applyTask($query, $action);
                    }
                });
            } else {
                foreach ($actions as $action) {
                    static::applyTask($query, $action);
                }
            }
        }
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $action
     * @return void
     */
    private static function applyTask(\Illuminate\Database\Eloquent\Builder &$query, array $action)
    {
        if (isset($action['scope'])) {
            $query->{$action['scope']}($action['value']);
        } else {
            $action['apply']($query, $action['field'], $action['value']);
        }
    }
}
