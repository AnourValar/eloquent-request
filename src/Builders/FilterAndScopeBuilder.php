<?php

namespace AnourValar\EloquentRequest\Builders;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Validator;

class FilterAndScopeBuilder extends AbstractBuilder
{
    /**
     * @var string
     */
    const OPTION_DO_NOT_GROUP = 'builder.filter_and_scope.do_not_group'; // prevents grouping for relations

    /**
     * @var string
     */
    const OPTION_CASTS_NOT_REQUIRED = 'builder.filter_and_scope.casts_not_required';

    /**
     * {@inheritDoc}
     * @see \AnourValar\EloquentRequest\Builders\BuilderInterface::build()
     */
    public function build(Builder &$query, array $profile, array $request, array $config, Validator &$validator) : void
    {
        parent::build($query, $profile, $request, $config, $validator);
        $tasks = [];

        // Get filters tasks
        foreach ((array)optional($request)[$config['filter_key']] as $field => $values) {
            foreach ((array)$values as $operation => $value) {
                $task = $this->getFilter($query, $field, $operation, $value);

                if ($task) {
                    $tasks[$task['key']][] = $task['value'];
                }
            }
        }

        // Get scopes tasks
        foreach ((array)optional($request)[$config['scope_key']] as $scope => $value) {
            $task = $this->getScope($scope, $value);

            if ($task) {
                $tasks[$task['key']][] = $task['value'];
            }
        }

        // Apply tasks
        $this->applyTasks($query, $tasks);
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $field
     * @param string $operation
     * @param mixed $value
     * @return array|NULL
     */
    protected function getFilter(Builder $query, string $field, string $operation, $value) : ?array
    {
        $key = $this->config['filter_key'];

        // Field described in profile?
        if (! isset($this->profile[$key][$field])) {
            $this->validator->after(function ($validator) use ($query, $key, $field, $operation)
            {
                $validator->errors()->add(
                    $key . '.' . $field . '.' . $operation,
                    trans(
                        'eloquent-request::validation.filter_not_supported',
                        ['attribute' => $this->getDisplayAttribute($query, $field, $this->profile)]
                    )
                );
            });

            return null;
        }
        $this->profile[$key][$field] = (array)$this->profile[$key][$field];

        // Operation described in profile?
        if (! in_array($operation, $this->profile[$key][$field])) {
            $this->validator->after(function ($validator) use ($query, $key, $field, $operation)
            {
                $validator->errors()->add(
                    $key . '.' . $field . '.' . $operation,
                    trans(
                        'eloquent-request::validation.operation_not_supported',
                        ['attribute' => $this->getDisplayAttribute($query, $field, $this->profile)]
                    )
                );
            });

            return null;
        }

        // Operation exists?
        if (! isset($this->config['filter_operations'][$operation])) {
            $this->validator->after(function ($validator) use ($query, $key, $field, $operation)
            {
                $validator->errors()->add(
                    $key . '.' . $field . '.' . $operation,
                    trans(
                        'eloquent-request::validation.operation_not_exists',
                        ['attribute' => $this->getDisplayAttribute($query, $field, $this->profile)]
                    )
                );
            });

            return null;
        }

        $path = explode('.', $field);
        $fieldFact = array_pop($path);
        $relation = implode('.', $path);


        // Handler's workflow
        $handler = $this->config['filter_operations'][$operation];
        if (! $handler instanceof \AnourValar\EloquentRequest\Builders\Operations\OperationInterface) {
            $handler = \App::make($handler);
        }

        if ($handler->cast()) {
            $value = $this->canonizeFilterValue($query, $relation, $fieldFact, $value);
        }

        if (! $handler->passes($value)) {
            return null;
        }

        $fail = $handler->validate($value, $this->getFailClosure());
        if ($fail) {
            $this->validator->after(function ($validator) use ($query, $key, $field, $operation, $fail)
            {
                $validator->errors()->add(
                    $key . '.' . $field . '.' . $operation,
                    trans(
                        $fail->message(),
                        $fail->params(['attribute' => $this->getDisplayAttribute($query, $field, $this->profile)])
                    )
                );
            });

            return null;
        }

        // Add task
        return [
            'key' => $relation,
            'value' => ['field' => $fieldFact, 'handler' => $handler, 'value' => $value],
        ];
    }

    /**
     * @param string $scope
     * @param mixed $value
     * @return array|NULL
     */
    protected function getScope(string $scope, $value) : ?array
    {
        $key = $this->config['scope_key'];

        // Described in profile?
        if (! in_array($scope, $this->profile[$key])) {
            $this->validator->after(function ($validator) use ($key, $scope)
            {
                $validator->errors()->add(
                    $key . '.' . $scope,
                    trans('eloquent-request::validation.scope_not_supported', ['scope' => $scope])
                );
            });

            return null;
        }

        $path = explode('.', $scope);
        $scopeFact = array_pop($path);
        $relation = implode('.', $path);

        return [
            'key' => $relation,
            'value' => ['scope' => $scopeFact, 'value' => $value, 'error_key' => $key . '.' . $scope],
        ];
    }

    /**
     * Builder: Filters & Scopes
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $tasks
     * @return void
     */
    protected function applyTasks(Builder &$query, array $tasks) : void
    {
        foreach ($tasks as $relation => $actions) {
            if ($relation && in_array(self::OPTION_DO_NOT_GROUP, $this->profile['options'])) {
                foreach ($actions as $action) {
                    $query->whereHas($relation, function ($query) use ($action)
                    {
                        $this->applyTask($query, $action);
                    });
                }
            } else if ($relation) {
                $query->whereHas($relation, function ($query) use ($actions)
                {
                    foreach ($actions as $action) {
                        $this->applyTask($query, $action);
                    }
                });
            } else {
                foreach ($actions as $action) {
                    $this->applyTask($query, $action);
                }
            }
        }
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $action
     * @return void
     */
    protected function applyTask(Builder &$query, array $action) : void
    {
        if (isset($action['scope'])) {
            $fail = $query->{$action['scope']}($action['value'], $this->getFailClosure());
            if ($fail instanceof \AnourValar\EloquentRequest\Helpers\Fail) {
                $this->validator->after(function ($validator) use ($action, $fail)
                {
                    $validator->errors()->add($action['error_key'], trans($fail->message(), $fail->params()));
                });
            }
        } else {
            $action['handler']->apply($query, $action['field'], $action['value']);
        }
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $relation
     * @param string $field
     * @param mixed $value
     * @return mixed
     */
    protected function canonizeFilterValue(Builder $query, ?string $relation, string $field, $value)
    {
        foreach (explode('.', $relation) as $relation) {
            if ($relation) {
                $query = $query->getModel()->$relation();
            }
        }
        $casts = $query->getModel()->getCasts();

        if (! isset($casts[$field])) {
            if (! in_array(self::OPTION_CASTS_NOT_REQUIRED, $this->profile['options'])) {
                $this->validator->after(function ($validator) use ($field)
                {
                    $validator->errors()->add(
                        $this->config['filter_key'] . '.' . $field,
                        "Cast is not set for attribute \"$field\"."
                    );
                });
            }

            return $value;
        }

        return $this->castValue($value, mb_strtolower($casts[$field]));
    }

    /**
     * @param mixed $value
     * @param string $cast
     * @return mixed
     */
    protected function castValue($value, string $cast)
    {
        if (is_scalar($value)) {
            if ($value === '') {
                return $value;
            }

            if (in_array($cast, ['int', 'integer'])) {
                return (int)$value;
            }

            if (in_array($cast, ['real', 'float', 'double'])) {
                return (float)$value;
            }

            if (in_array($cast, ['date'])) {
                return date('Y-m-d', strtotime($value));
            }

            if (in_array($cast, ['datetime', 'timestamp'])) {
                preg_match('|^\d{2,4}([\/\.\-])\d{2,4}\1\d{2,4}(.*)$|', $value, $check);

                if ($check && stripos($check[2], ':')) {
                    return date('Y-m-d H:i:s', strtotime($value));
                }
                return date('Y-m-d', strtotime($value));
            }

            return $value;
        }

        if (is_array($value)) {
            foreach ($value as &$item) {
                $item = $this->castValue($item, $cast);
            }
            unset($item);

            return $value;
        }

        return null;
    }
}
