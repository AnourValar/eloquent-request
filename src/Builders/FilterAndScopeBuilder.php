<?php

namespace AnourValar\EloquentRequest\Builders;

use Illuminate\Database\Eloquent\Builder;
use AnourValar\EloquentRequest\Validators\ValidatorInterface;

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
    public function build(Builder &$query, array $profile, array $request, array $config, ValidatorInterface &$validator): void
    {
        parent::build($query, $profile, $request, $config, $validator);
        $tasks = [];

        // Get filters tasks
        foreach ((array)optional($request)[$config['filter_key']] as $field => $values) {
            if (is_numeric($field)) {
                continue;
            }

            foreach ((array)$values as $operation => $value) {
                $task = $this->getFilter($query, $field, $operation, $value);

                if ($task) {
                    $tasks[$task['key']][] = $task['value'];
                }
            }
        }

        // Get scopes tasks
        foreach ((array)optional($request)[$config['scope_key']] as $scope => $value) {
            if (is_numeric($scope)) {
                continue;
            }

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
    protected function getFilter(Builder $query, string $field, string $operation, $value): ?array
    {
        $key = $this->config['filter_key'];

        // Field described in profile?
        $parsedField = $this->parseField($this->profile[$key], $field);
        if (! isset($parsedField)) {
            $this->validator->addError(
                [$key, $field, $operation],
                trans('eloquent-request::validation.filter_not_supported', ['attribute' => $field])
            );

            return null;
        }

        // Operation described in profile?
        if (! in_array($operation, $this->profile[$key][$parsedField], true)) {
            $this->validator->addError(
                [$key, $field, $operation],
                trans(
                    'eloquent-request::validation.operation_not_supported',
                    ['attribute' => $this->getDisplayAttribute($query, [$field, $parsedField], $this->profile)]
                )
            );

            return null;
        }

        // Operation exists?
        if (! isset($this->config['filter_operations'][$operation])) {
            $this->validator->addError(
                [$key, $field, $operation],
                trans(
                    'eloquent-request::validation.operation_not_exists',
                    ['attribute' => $this->getDisplayAttribute($query, [$field, $parsedField], $this->profile)]
                )
            );

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

        if (! $this->validateRanges($query, $field, $value, $operation)) {
            return null;
        }

        try {
            $handler->validate($value, $this->getFailClosure());
        } catch (\AnourValar\EloquentRequest\Helpers\FailException $e) {
            $this->validator->addError(
                [$key, $field, $operation],
                trans(
                    $e->getMessage(),
                    $e->getParams(['attribute' => $this->getDisplayAttribute($query, [$field, $parsedField], $this->profile)])
                )
            );

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
    protected function getScope(string $scope, $value): ?array
    {
        $key = $this->config['scope_key'];

        // Described in profile?
        if (! in_array($scope, $this->profile[$key], true)) {
            $this->validator->addError(
                [$key, $scope],
                trans('eloquent-request::validation.scope_not_supported', ['scope' => $scope])
            );

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
    protected function applyTasks(Builder &$query, array $tasks): void
    {
        foreach ($tasks as $relation => $actions) {
            if ($relation && in_array(self::OPTION_DO_NOT_GROUP, $this->profile['options'])) {
                foreach ($actions as $action) {
                    $query->whereHas($relation, function ($query) use ($action)
                    {
                        $this->applyTask($query, $action);
                    });
                }
            } elseif ($relation) {
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
    protected function applyTask(Builder &$query, array $action): void
    {
        if (isset($action['scope'])) {
            try {
                $query->{$action['scope']}($action['value']);
            } catch (\Illuminate\Validation\ValidationException $e) {
                foreach ($e->validator->errors()->messages() as $key => $items) {
                    foreach ((array)$items as $item) {
                        $this->validator->addError(
                            [$action['error_key'], $key],
                            trans($item, ['attribute' => $this->getDisplayAttribute($query, $key, $this->profile)])
                        );
                    }
                }
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
        $parsedField = $this->parseField($casts, $field);

        if (! isset($casts[$parsedField])) {
            if (! in_array(self::OPTION_CASTS_NOT_REQUIRED, $this->profile['options'])) {
                $this->validator->addError(
                    [$this->config['filter_key'], $field],
                    "Cast is not set for the attribute \"$field\"."
                );
            }

            return $value;
        }

        return $this->castValue($value, mb_strtolower($casts[$parsedField]));
    }

    /**
     * @param mixed $value
     * @param string $cast
     * @return mixed
     */
    protected function castValue($value, string $cast)
    {
        if (is_scalar($value)) {
            if (in_array($cast, ['int', 'integer'])) {
                return (int)$value;
            }

            if (in_array($cast, ['real', 'float', 'double'])) {
                return (float)$value;
            }

            if ($value === '') {
                return $value;
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

    /**
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $field
     * @param mixed $value
     * @param string $operation
     * @return boolean
     */
    protected function validateRanges(Builder $query, string $field, $value, string $operation): bool
    {
        $key = $this->config['filter_key'];

        if (is_scalar($value)) {
            if (isset($this->profile['ranges'][$field]['min']) && $this->profile['ranges'][$field]['min'] > $value) {
                $this->validator->addError(
                    [$key, $field, $operation],
                    trans(
                        'eloquent-request::validation.ranges.min',
                        [
                            'attribute' => $this->getDisplayAttribute($query, $field, $this->profile),
                            'min' => ( $this->profile['ranges'][$field]['min'] ?? null ),
                            'max' => ( $this->profile['ranges'][$field]['max'] ?? null ),
                        ]
                    )
                );

                return false;
            }

            if (isset($this->profile['ranges'][$field]['max']) && $this->profile['ranges'][$field]['max'] < $value) {
                $this->validator->addError(
                    [$key, $field, $operation],
                    trans(
                        'eloquent-request::validation.ranges.max',
                        [
                            'attribute' => $this->getDisplayAttribute($query, $field, $this->profile),
                            'min' => ( $this->profile['ranges'][$field]['min'] ?? null ),
                            'max' => ( $this->profile['ranges'][$field]['max'] ?? null ),
                        ]
                    )
                );

                return false;
            }
        }

        if (is_array($value)) {
            foreach ($value as $item) {
                if (! $this->validateRanges($query, $field, $item, $operation)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * @param string $field
     * @return string|NULL
     */
    protected function parseField(array $data, string $key): ?string
    {
        // full match
        if (isset($data[$key])) {
            return $key;
        }

        // json path
        $key = explode('->', $key);
        while (count($key) > 1) {
            array_pop($key);
            $pattern = implode('->', $key) . '->*';

            if (isset($data[$pattern])) {
                return $pattern;
            }
        }

        // nothing was found
        return null;
    }
}
