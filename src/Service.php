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
    const PROFILE_PRESET_ID = ['=', '!=', 'in', 'not-in'];
    const PROFILE_PRESET_BOOLEAN = ['=', '!=', 'in', 'not-in'];
    const PROFILE_PRESET_NUMBER = ['=', '!=', '<', '<=', '>', '>=', 'in', 'not-in'];
    const PROFILE_PRESET_DATE = ['=', '!=', '<', '<=', '>', '>=', 'in', 'not-in'];
    const PROFILE_PRESET_TEXT = ['=', '!=', 'like', 'not-like'];
    const PROFILE_PRESET_IS_NULL = ['is-null'];

    /**
     * Config
     *
     * @var array
     */
    protected $config = [
        // actions
        'per_page_key' => 'per_page',
        'page_key' => 'page',

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
        ],

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
        'dump' => \AnourValar\EloquentRequest\Actions\DumpAction::class,
        'get' => \AnourValar\EloquentRequest\Actions\GetAction::class,
        'paginate' => \AnourValar\EloquentRequest\Actions\PaginateAction::class,
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
     * @throws \Exception
     * @throws \Illuminate\Validation\ValidationException
     * @return \Illuminate\Support\Collection|\Illuminate\Pagination\LengthAwarePaginator|\Illuminate\Pagination\Paginator
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
            throw new \Exception('First argument must to be Eloquent model.');
        }


        // Other prepares
        $validator = \Validator::make([], []);

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
                $validator->after(function ($validator) use ($e)
                {
                    $validator->errors()->add(($e->getSuffix() ?? 'action'), trans($e->getMessage(), $e->getParams()));
                });
            }
            $validator->validate();

            // Handle
            try {
                $collection = $action->action($query, $profile, $request, $this->config, $this->getFailClosure());
            } catch (\AnourValar\EloquentRequest\Helpers\FailException $e) {
                \Validator
                    ::make([], [])
                    ->after(function ($validator) use ($e)
                    {
                        $validator->errors()->add(
                            ($e->getSuffix() ?? 'action'),
                            trans($e->getMessage(), $e->getParams())
                        );
                    })
                    ->validate();
            }

            if ($collection instanceof \Illuminate\Support\Collection ||
                $collection instanceof \Illuminate\Pagination\LengthAwarePaginator ||
                $collection instanceof \Illuminate\Pagination\Paginator ||
                $collection instanceof \Illuminate\Database\Eloquent\Collection
            ) {
                event(new RequestBuiltEvent($collection, $profile, $request, $this->config, $actionName));

                return $collection;
            }

            throw new \Exception('Unexpected return data.');
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
    public function getBuildRequest(array $profile, array $request) : \AnourValar\EloquentRequest\Helpers\Request
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
    public function extendActions(string $name, ?callable $action) : self
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
    public function extendBuilders(string $name, ?callable $builder) : self
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
    public function replaceConfig(array $config) : self
    {
        $this->config = array_replace_recursive($this->config, $config);

        return $this;
    }

    /**
     * @param array $profile
     * @return array
     */
    private function prepareProfile(array $profile) : array
    {
        return array_replace(
            [
                $this->config['filter_key'] => [],
                $this->config['scope_key'] => [],
                $this->config['sort_key'] => [],

                'options' => [],
                'default_request' => [],
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
    private function prepareRequest(array $profile, array $request) : \AnourValar\EloquentRequest\Helpers\Request
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
            $this->config
        );
    }
}
