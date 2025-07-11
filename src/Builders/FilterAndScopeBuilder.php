<?php

namespace AnourValar\EloquentRequest\Builders;

use AnourValar\EloquentRequest\Validators\ValidatorInterface;
use Illuminate\Database\Eloquent\Builder;

class FilterAndScopeBuilder extends AbstractBuilder
{
    /**
     * @var string
     */
    public const OPTION_GROUP_RELATION = 'builder.filter_and_scope.group_relation'; // combine filters of the same relation

    /**
     * @var string
     */
    public const OPTION_CASTS_NOT_REQUIRED = 'builder.filter_and_scope.casts_not_required';

    /**
     * @var int
     */
    protected const RELATION_LIMIT = 10000;

    /**
     * {@inheritDoc}
     * @see \AnourValar\EloquentRequest\Builders\BuilderInterface::build()
     */
    public function build(Builder &$query, array $profile, array $request, array $config, ValidatorInterface &$validator): array
    {
        parent::build($query, $profile, $request, $config, $validator);
        $tasks = [];
        $buildRequest = [];

        // Get filters tasks
        foreach ((array) optional($request)[$config['filter_key']] as $field => $values) {
            if (is_numeric($field)) {
                continue;
            }

            foreach ((array) $values as $operation => $value) {
                $task = $this->getFilter($query, $field, $operation, $value);

                if ($task) {
                    $buildRequest[$config['filter_key']][$field][$operation] = $value;
                    $tasks[$task['key']][] = $task['value'];
                }
            }
        }

        // Get relations tasks
        foreach ((array) optional($request)[$config['relation_key']] as $relation => $values) {
            if (is_numeric($relation)) {
                continue;
            }

            foreach ((array) $values as $operation => $value) {
                $task = $this->getRelation($query, $relation, $operation, $value);

                if ($task) {
                    $buildRequest[$config['relation_key']][$relation][$operation] = $value;
                    $tasks[$task['key']][] = $task['value'];
                }
            }
        }

        // Get scopes tasks
        foreach ((array) optional($request)[$config['scope_key']] as $scope => $value) {
            if (is_numeric($scope)) {
                continue;
            }

            $task = $this->getScope($scope, $value);

            if ($task) {
                $buildRequest[$config['scope_key']][$scope] = $value;
                $tasks[$task['key']][] = $task['value'];
            }
        }

        // Apply tasks
        $this->applyTasks($query, $tasks);

        return $buildRequest;
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $field
     * @param string $operation
     * @param mixed $value
     * @return array|null
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
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $relation
     * @param string $operation
     * @param mixed $value
     * @return array|null
     */
    protected function getRelation(Builder $query, string $relation, string $operation, $value): ?array
    {
        if (is_null($value)) {
            return null;
        }

        $key = $this->config['relation_key'];

        // Relation described in profile?
        if (! in_array($relation, $this->profile[$key], true)) {
            $this->validator->addError(
                [$key, $relation, $operation],
                trans('eloquent-request::validation.relation_not_supported', ['relation' => $relation])
            );

            return null;
        }

        // Operation exists?
        if (! in_array($operation, ['min', 'max'], true)) {
            $this->validator->addError(
                [$key, $relation, $operation],
                trans('eloquent-request::validation.relation_operation_not_supported', ['relation' => $relation])
            );

            return null;
        }

        // Value in range?
        if (! is_numeric($value) || $value != (int) $value || $value < 0 || $value > static::RELATION_LIMIT) {
            $this->validator->addError(
                [$key, $relation, $operation],
                trans(
                    'eloquent-request::validation.relation_out_of_range',
                    ['relation' => $this->getDisplayAttribute($query, $relation, $this->profile), 'max' => static::RELATION_LIMIT]
                )
            );

            return null;
        }

        // Add task
        return [
            'key' => '',
            'value' => ['relation' => $relation, $operation => $value],
        ];
    }

    /**
     * @param string $scope
     * @param mixed $value
     * @return array|null
     */
    protected function getScope(string $scope, $value): ?array
    {
        $key = $this->config['scope_key'];

        // Described in profile?
        $closure = ($this->profile[$key][$scope] ?? null);
        if (! $closure instanceof \Closure) {
            $closure = null;
        }

        if (! in_array($scope, $this->profile[$key], true) && ! $closure) {
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
            'value' => ['scope' => $scopeFact, 'closure' => $closure, 'value' => $value, 'error_key' => $key . '.' . $scope],
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
            if ($relation && ! in_array(self::OPTION_GROUP_RELATION, $this->profile['options'])) {
                foreach ($actions as $action) {
                    $query->whereHas($relation, function ($query) use ($action) {
                        $this->applyTask($query, $action);
                    });
                }
            } elseif ($relation) {
                $query->whereHas($relation, function ($query) use ($actions) {
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
        if (isset($action['relation'])) {
            if (isset($action['min']) && $action['min'] == 1) {
                $query->has($action['relation']);
            } elseif (isset($action['min']) && $action['min'] > 0) {
                $query->has($action['relation'], '>=', $action['min']);
            } elseif (isset($action['max']) && $action['max'] == 0) {
                $query->doesntHave($action['relation']);
            } elseif (isset($action['max'])) {
                $query->has($action['relation'], '<=', $action['max']);
            }
        } elseif (isset($action['scope'])) {
            try {
                if ($action['closure']) {
                    $action['closure']($query, $action['value']);
                } else {
                    $query->{$action['scope']}($action['value']);
                }
            } catch (\Illuminate\Validation\ValidationException $e) {
                foreach ($e->validator->errors()->messages() as $key => $items) {
                    foreach ((array) $items as $item) {
                        $this->validator->addError(
                            [$action['error_key'], $key],
                            trans($item, ['attribute' => $this->getDisplayAttribute($query, $key, $this->profile)])
                        );
                    }
                }
            }
        } else {
            $action['handler']->apply($query, $this->getColumnFullname($query, $action['field']), $action['value'], $this->profile['options']);
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

        // casts from profile
        $casts = [];
        foreach ($this->profile['custom_casts'] as $key => $cast) {
            if (! $relation) {
                if (! strpos($key, '.')) {
                    $casts[$key] = $cast;
                }
            } elseif (strpos($key, $relation . '.') === 0) {
                $casts[mb_substr($key, (mb_strlen($relation) + 1))] = $cast;
            }
        }
        $parsedField = $this->parseField($casts, $field);

        // casts from model
        if (! isset($casts[$parsedField])) {
            $casts = $query->getModel()->getCasts();
            $parsedField = $this->parseField($casts, $field);
        }

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
                return (int) $value;
            }

            if (in_array($cast, ['real', 'float', 'double'])) {
                return (float) $value;
            }

            if (in_array($cast, ['string'])) {
                return (string) $value;
            }

            if ($value === '') {
                return $value;
            }

            if (in_array($cast, ['date', 'immutable_date'])) {
                return date('Y-m-d', strtotime($value));
            }

            if (in_array($cast, ['datetime', 'immutable_datetime'])) {
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
     * @throws \LogicException
     * @return bool
     */
    protected function validateRanges(Builder $query, string $field, $value, string $operation): bool
    {
        $parsedField = $this->parseField($this->profile['ranges'], $field);
        if (! $parsedField) {
            return true;
        }

        if (! is_array($this->profile['ranges'][$parsedField])) {
            throw new \LogicException('Range must be an array.');
        }

        if (is_scalar($value)) {
            if (isset($this->profile['ranges'][$parsedField]['min']) && $this->profile['ranges'][$parsedField]['min'] > $value) {
                $this->validator->addError(
                    [$this->config['filter_key'], $field, $operation],
                    trans(
                        'eloquent-request::validation.ranges.min',
                        [
                            'attribute' => $this->getDisplayAttribute($query, [$field, $parsedField], $this->profile),
                            'min' => $this->profile['ranges'][$parsedField]['min'],
                            'max' => ($this->profile['ranges'][$parsedField]['max'] ?? null),
                        ]
                    )
                );

                return false;
            }

            if (isset($this->profile['ranges'][$parsedField]['max']) && $this->profile['ranges'][$parsedField]['max'] < $value) {
                $this->validator->addError(
                    [$this->config['filter_key'], $field, $operation],
                    trans(
                        'eloquent-request::validation.ranges.max',
                        [
                            'attribute' => $this->getDisplayAttribute($query, [$field, $parsedField], $this->profile),
                            'min' => ($this->profile['ranges'][$parsedField]['min'] ?? null),
                            'max' => $this->profile['ranges'][$parsedField]['max'],
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
}
