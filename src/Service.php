<?php

namespace AnourValar\EloquentRequest;

/**
 * // Handler example (cache default request)
 * handler: function ($runAction, $buildRequest) {
 *     if ($buildRequest->hasOnly([])) {
 *         return \Cache::remember('list', 86400, fn () => $runAction());
 *     }
 *     return $runAction();
 * }
 *
 * // Handler example (cache page requests)
 * handler: function ($runAction, $buildRequest) {
 *     if ($buildRequest->hasOnly(['page'])) {
 *         return \Cache::remember('list:' . $buildRequest->get('page'), 86400, fn () => $runAction());
 *     }
 *     return $runAction();
 * }
 *
 * // Handler example (cache all requests)
 * handler: function ($runAction, $buildRequest) {
 *     return \Cache::remember($buildRequest->cacheKey(), 86400, fn () => $runAction());
 * }
 *
 */

class Service
{
    use \AnourValar\EloquentRequest\Helpers\ValidationTrait;

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
        'relation_key' => 'relation',
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
     * @param mixed $buildRequest
     * @param callable|null $handler
     * @return mixed
     * @throws \Illuminate\Validation\ValidationException
     * @throws \RuntimeException
     */
    public function buildBy($query, array $profile, array $request, &$buildRequest = null, ?callable $handler = null)
    {
        // Prepare query builder
        if (is_string($query)) {
            $query = new $query();
        }

        if ($query instanceof \Illuminate\Database\Eloquent\Model) {
            $query = $query->newQuery();
        }

        if (! $query instanceof \Illuminate\Database\Eloquent\Builder) {
            throw new \RuntimeException('First argument must to be Eloquent model.');
        }


        // Other prepares
        $validator = $this->config['validator'];
        if (! $validator instanceof \AnourValar\EloquentRequest\Validators\ValidatorInterface) {
            $validator = \App::make($validator);
        }

        $profile = $this->prepareProfile($profile);
        $request = $this->prepareRequest($profile, $request);
        $buildRequest = [];


        // Builders
        foreach ($this->builders as $builder) {
            if (! $builder instanceof \AnourValar\EloquentRequest\Builders\BuilderInterface) {
                $builder = \App::make($builder);
            }

            $buildRequest = array_replace($buildRequest, $builder->build($query, $profile, $request, $this->config, $validator));
        }


        // Actions
        foreach ($this->actions as $action) {
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

            // Build request
            $requestParameters = $action->requestParameters($profile, $request, $this->config);
            $buildRequest = array_merge($buildRequest, $requestParameters);
            $buildRequest = new \AnourValar\EloquentRequest\Helpers\Request($buildRequest, $profile, $this->config, $query);

            // Handle
            try {
                $runAction = fn () => $action->action($query, $profile, $requestParameters, $this->config, $this->getFailClosure());

                if ($handler) {
                    return $handler($runAction, $buildRequest);
                }
                return $runAction();
            } catch (\AnourValar\EloquentRequest\Helpers\FailException $e) {
                $validator
                    ->addError($e->getSuffix('action'), trans($e->getMessage(), $e->getParams()))
                    ->validate($profile, $this->config);
            }
        }

        throw new \RuntimeException('No actions are available for the request.'); // return collect();
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
        $profile['default_request'] = array_replace(
            [$this->config['per_page_key'] => 20, $this->config['page_key'] => 1, $this->config['cursor_key'] => null],
            $profile['default_request'] ?? []
        );

        return array_replace(
            [
                $this->config['filter_key'] => [],
                $this->config['relation_key'] => [],
                $this->config['scope_key'] => [],
                $this->config['sort_key'] => [],

                'ranges' => [],

                'options' => [],
                'default_request' => [],

                'custom_casts' => [],
                'custom_attributes' => [],
                'custom_attributes_path' => null,
                'custom_attributes_handler' => null,
            ],
            $this->config['default_profile'],
            $profile
        );
    }

    /**
     * @param array $profile
     * @param array $request
     * @return array
     */
    private function prepareRequest(array $profile, array $request): array
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

        return array_replace($profile['default_request'], $request);
    }
}
