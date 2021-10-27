<?php

namespace AnourValar\EloquentRequest;

use AnourValar\EloquentRequest\Events\RequestBuiltEvent;

class Service
{
    use \AnourValar\EloquentRequest\Helpers\ValidationTrait;

    /**
     * Presets of availables operations
     *
     * @var array
     */
    const PROFILE_FILTER_ID = ['=', '!=', 'in', 'not-in'];
    const PROFILE_FILTER_BOOLEAN = ['=', '!=', 'in', 'not-in'];
    const PROFILE_FILTER_NUMBER = ['=', '!=', '<', '<=', '>', '>=', 'in', 'not-in'];
    const PROFILE_FILTER_DATE = ['=', '!=', '<', '<=', '>', '>=', 'in', 'not-in'];
    const PROFILE_FILTER_TEXT = ['=', '!=', 'like', 'not-like'];
    const PROFILE_FILTER_IS_NULL = ['is-null'];
    const PROFILE_FILTER_SEARCH = ['search'];
    const PROFILE_FILTER_JSON = ['json-in', 'json-contains', 'json-not-in', 'json-not-contains'];

    /**
     * Presets of availables ranges
     *
     * @var array
     */
    const PROFILE_RANGE_TINYINT = 127;
    const PROFILE_RANGE_UNSIGNED_TINYINT = 255; // MySQL

    const PROFILE_RANGE_SMALLINT = 32767;
    const PROFILE_RANGE_UNSIGNED_SMALLINT = 65535; // MySQL

    const PROFILE_RANGE_MEDIUMINT = 8388607;
    const PROFILE_RANGE_UNSIGNED_MEDIUMINT = 16777215; // MySQL

    const PROFILE_RANGE_INT = 2147483647;
    const PROFILE_RANGE_UNSIGNED_INT = 4294967295; // MySQL

    /**
     * Config
     *
     * @var array
     */
    protected $config = [
        // actions
        'per_page_key' => 'per_page',
        'page_key' => 'page',
        'cursor_key' => 'cursor',

        // builders
        'filter_key' => 'filter',
        'scope_key' => 'scope',
        'sort_key' => 'sort',

        'filter_operations' => [
            '=' => \AnourValar\EloquentRequest\Builders\Operations\EqOperation::class,
            '!=' => \AnourValar\EloquentRequest\Builders\Operations\NotEqOperation::class,

            '<' => \AnourValar\EloquentRequest\Builders\Operations\LtOperation::class,
            '<=' => \AnourValar\EloquentRequest\Builders\Operations\LeOperation::class,
            '>' => \AnourValar\EloquentRequest\Builders\Operations\GtOperation::class,
            '>=' => \AnourValar\EloquentRequest\Builders\Operations\GeOperation::class,

            'search' => \AnourValar\EloquentRequest\Builders\Operations\SearchOperation::class,
            'like' => \AnourValar\EloquentRequest\Builders\Operations\LikeOperation::class,
            'not-like' => \AnourValar\EloquentRequest\Builders\Operations\NotLikeOperation::class,

            'in' => \AnourValar\EloquentRequest\Builders\Operations\InOperation::class,
            'not-in' => \AnourValar\EloquentRequest\Builders\Operations\NotInOperation::class,

            'is-null' => \AnourValar\EloquentRequest\Builders\Operations\IsNullOperation::class,

            'json-in' => \AnourValar\EloquentRequest\Builders\Operations\JsonInOperation::class,
            'json-contains' => \AnourValar\EloquentRequest\Builders\Operations\JsonContainsOperation::class,
            'json-not-in' => \AnourValar\EloquentRequest\Builders\Operations\JsonNotInOperation::class,
            'json-not-contains' => \AnourValar\EloquentRequest\Builders\Operations\JsonNotContainsOperation::class,
        ],

        // validator
        'validator' => \AnourValar\EloquentRequest\Validators\IlluminateValidator::class,
        'validator_key_delimiter' => '.',

        // etc
        'default_profile' => [
            'adapter' => \AnourValar\EloquentRequest\Adapters\CanonicalAdapter::class,
        ],
    ];

    /**
     * Actions
     *
     * @var array
     */
    protected $actions = [
        'null' => \AnourValar\EloquentRequest\Actions\NullAction::class,
        'generator' => \AnourValar\EloquentRequest\Actions\GeneratorAction::class,
        'get' => \AnourValar\EloquentRequest\Actions\GetAction::class,
        'cursor' => \AnourValar\EloquentRequest\Actions\CursorAction::class,
        'cursor_paginate' => \AnourValar\EloquentRequest\Actions\CursorPaginateAction::class,
        'paginate' => \AnourValar\EloquentRequest\Actions\PaginateAction::class, // default
    ];

    /**
     * Builders
     *
     * @var array
     */
    protected $builders = [
        'filter-scope' => \AnourValar\EloquentRequest\Builders\FilterAndScopeBuilder::class,
        'sort' => \AnourValar\EloquentRequest\Builders\SortBuilder::class,
    ];

    /**
     * Build request
     *
     * @param mixed $query
     * @param array $profile
     * @param array $request
     * @throws \LogicException
     * @throws \Illuminate\Validation\ValidationException
     * @return mixed
     */
    public function buildBy($query, array $profile, array $request)
    {
        // Prepare query builder
        if (is_string($query)) {
            $query = new $query;
        }

        if ($query instanceof \Illuminate\Database\Eloquent\Model) {
            $query = $query->newQuery();
        }

        if (! $query instanceof \Illuminate\Database\Eloquent\Builder) {
            throw new \LogicException('First argument must to be Eloquent model.');
        }


        // Other prepares
        $validator = $this->config['validator'];
        if (! $validator instanceof \AnourValar\EloquentRequest\Validators\ValidatorInterface) {
            $validator = \App::make($validator);
        }

        $profile = $this->prepareProfile($profile);
        $request = $this->prepareRequest($profile, $request)->get();


        // Builders
        foreach ($this->builders as $builder) {
            if (! $builder instanceof \AnourValar\EloquentRequest\Builders\BuilderInterface) {
                $builder = \App::make($builder);
            }

            $builder->build($query, $profile, $request, $this->config, $validator);
        }


        // Actions
        foreach ($this->actions as $actionName => $action) {
            if (! $action instanceof \AnourValar\EloquentRequest\Actions\ActionInterface) {
                $action = \App::make($action);
            }

            // Can handle?
            if (! $action->passes($profile, $request, $this->config)) {
                continue;
            }

            // Validation (at last)
            try {
                $action->validate($profile, $request, $this->config, $this->getFailClosure());
            } catch (\AnourValar\EloquentRequest\Helpers\FailException $e) {
                $validator->addError($e->getSuffix('action'), trans($e->getMessage(), $e->getParams()));
            }
            $validator->validate($profile, $this->config);

            // Handle
            try {
                $result = $action->action($query, $profile, $request, $this->config, $this->getFailClosure());
            } catch (\AnourValar\EloquentRequest\Helpers\FailException $e) {
                $validator
                    ->addError($e->getSuffix('action'), trans($e->getMessage(), $e->getParams()))
                    ->validate($profile, $this->config);
            }

            event(new RequestBuiltEvent($result, $profile, $request, $this->config, $actionName));
            return $result;
        }

        return collect();
    }

    /**
     * Get request data
     *
     * @param array $profile
     * @param array $request
     * @return \AnourValar\EloquentRequest\Helpers\Request
     */
    public function getBuildRequest(array $profile, array $request): \AnourValar\EloquentRequest\Helpers\Request
    {
        $profile = $this->prepareProfile($profile);

        return $this->prepareRequest($profile, $request);
    }

    /**
     * Prepend action
     *
     * @param string $name
     * @param callable $action
     * @return \AnourValar\EloquentRequest\Service
     */
    public function extendActions(string $name, ?callable $action): self
    {
        $this->actions = [$name => $action] + $this->actions;

        if (! isset($this->actions[$name])) {
            unset($this->actions[$name]);
        }

        return $this;
    }

    /**
     * Add builder
     *
     * @param string $name
     * @param callable $builder
     * @return \AnourValar\EloquentRequest\Service
     */
    public function extendBuilders(string $name, ?callable $builder): self
    {
        $this->builders = [$name => $builder] + $this->builders;

        if (! isset($this->builders[$name])) {
            unset($this->builders[$name]);
        }

        return $this;
    }

    /**
     * Replace config
     *
     * @param array $config
     * @return \AnourValar\EloquentRequest\Service
     */
    public function replaceConfig(array $config): self
    {
        $this->config = array_replace_recursive($this->config, $config);

        return $this;
    }

    /**
     * @param array $profile
     * @return array
     */
    private function prepareProfile(array $profile): array
    {
        return array_replace(
            [
                $this->config['filter_key'] => [],
                $this->config['scope_key'] => [],
                $this->config['sort_key'] => [],

                'ranges' => [],

                'options' => [],
                'default_request' => [],

                'custom_casts' => [],
                'custom_attributes_path' => null,
            ],
            $this->config['default_profile'],
            $profile
        );
    }

    /**
     * @param array $profile
     * @param array $request
     * @return \AnourValar\EloquentRequest\Helpers\Request
     */
    private function prepareRequest(array $profile, array $request): \AnourValar\EloquentRequest\Helpers\Request
    {
        $adapter = $profile['adapter'];
        if (! $adapter instanceof \AnourValar\EloquentRequest\Adapters\AdapterInterface) {
            $adapter = \App::make($adapter);
        }

        foreach (array_keys($request) as $key) {
            if (stripos($key, '_') === 0) {
                unset($request[$key]);
            }
        }

        $request = $adapter->canonize($request, $profile, $this->config);

        return new \AnourValar\EloquentRequest\Helpers\Request(
            array_replace($profile['default_request'], $request),
            $profile,
            $this->config
        );
    }
}
