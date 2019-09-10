<?php

namespace AnourValar\EloquentRequest\Services;

use \AnourValar\EloquentRequest\Events\RequestBuiltEvent;

class EloquentRequestService
{
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

    /**
     * Options
     *
     * @var array
     */
    protected $options = [
        // actions
        'per_page_key' => 'per_page',
        'page_key' => 'page',

        // builders
        'sort_key' => 'sort',
        'scope_key' => 'scope',
        'filter_key' => 'filter',

        'filter_operations' => [
            '=' => [
                'validate' => [\AnourValar\EloquentRequest\Builders\Operations\EqOperation::class, 'validate'],
                'error_message' => 'eloquent-request::validation.scalar',
                'apply' => [\AnourValar\EloquentRequest\Builders\Operations\EqOperation::class, 'eq'],
            ],
            '!=' => [
                'validate' => [\AnourValar\EloquentRequest\Builders\Operations\EqOperation::class, 'validate'],
                'error_message' => 'eloquent-request::validation.scalar',
                'apply' => [\AnourValar\EloquentRequest\Builders\Operations\EqOperation::class, 'notEq'],
            ],

            '<' => [
                'validate' => 'is_scalar',
                'error_message' => 'eloquent-request::validation.scalar',
                'apply' => [\AnourValar\EloquentRequest\Builders\Operations\LessGreaterOperation::class, 'lt'],
            ],
            '<=' => [
                'validate' => 'is_scalar',
                'error_message' => 'eloquent-request::validation.scalar',
                'apply' => [\AnourValar\EloquentRequest\Builders\Operations\LessGreaterOperation::class, 'le'],
            ],
            '>' => [
                'validate' => 'is_scalar',
                'error_message' => 'eloquent-request::validation.scalar',
                'apply' => [\AnourValar\EloquentRequest\Builders\Operations\LessGreaterOperation::class, 'gt'],
            ],
            '>=' => [
                'validate' => 'is_scalar',
                'error_message' => 'eloquent-request::validation.scalar',
                'apply' => [\AnourValar\EloquentRequest\Builders\Operations\LessGreaterOperation::class, 'ge'],
            ],

            'like' => [
                'validate' => [\AnourValar\EloquentRequest\Builders\Operations\LikeOperation::class, 'validate'],
                'error_message' => 'eloquent-request::validation.like',
                'apply' => [\AnourValar\EloquentRequest\Builders\Operations\LikeOperation::class, 'like'],
            ],
            'not-like' => [
                'validate' => [\AnourValar\EloquentRequest\Builders\Operations\LikeOperation::class, 'validate'],
                'error_message' => 'eloquent-request::validation.like',
                'apply' => [\AnourValar\EloquentRequest\Builders\Operations\LikeOperation::class, 'notLike'],
            ],

            'in' => [
                'validate' => [\AnourValar\EloquentRequest\Builders\Operations\InOperation::class, 'validate'],
                'error_message' => 'eloquent-request::validation.list',
                'apply' => [\AnourValar\EloquentRequest\Builders\Operations\InOperation::class, 'in'],
            ],
            'not-in' => [
                'validate' => [\AnourValar\EloquentRequest\Builders\Operations\InOperation::class, 'validate'],
                'error_message' => 'eloquent-request::validation.list',
                'apply' => [\AnourValar\EloquentRequest\Builders\Operations\InOperation::class, 'notIn'],
            ],
        ],
    ];

    /**
     * Builders
     *
     * @var array
     */
    protected $builders = [
        'filter-scope' => [\AnourValar\EloquentRequest\Builders\FilterAndScopeBuilder::class, 'build'],
        'sort' => [\AnourValar\EloquentRequest\Builders\SortBuilder::class, 'build'],
    ];

    /**
     * Actions
     *
     * @var array
     */
    protected $actions = [
        'paginate' => [\AnourValar\EloquentRequest\Actions\PaginateAction::class, 'act'],
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

        // Prepare validation
        $validator = \Validator::make([], []);
        if (method_exists($query->getModel(), 'getAttributeNames')) {
            $validator->setAttributeNames($query->getModel()->getAttributeNames());
        }

        // Prepare profile & request
        $request = array_replace(($profile['default_request'] ?? []), $request);

        // Builders
        foreach ($this->builders as $builder) {
            $builder($query, $profile, $request, $this->options, $validator);
        }

        // Validation
        $validator->validate();

        // Actions
        foreach ($this->actions as $actionName => $action) {
            $collection = $action($query, $profile, $request, $this->options);

            if ($collection instanceof \Illuminate\Support\Collection ||
                $collection instanceof \Illuminate\Pagination\LengthAwarePaginator ||
                $collection instanceof \Illuminate\Pagination\Paginator
            ) {
                event(new RequestBuiltEvent($collection, $profile, $request, $actionName));

                return $collection;
            }

            if ($collection) {
                throw new \Exception('Unexpected return data in action.');
            }
        }

        return collect(); // just in case :)
    }

    /**
     * Prepend action
     *
     * @param string $name
     * @param callable $action
     * @return \AnourValar\EloquentRequest\Services\EloquentRequestService
     */
    public function extendActions(string $name, ?callable $action)
    {
        $this->actions = [$name => $action] + $this->actions;

        if (!isset($this->actions[$name])) {
            unset($this->actions[$name]);
        }

        return $this;
    }

    /**
     * Add builder
     *
     * @param string $name
     * @param callable $builder
     * @return \AnourValar\EloquentRequest\Services\EloquentRequestService
     */
    public function extendBuilders(string $name, ?callable $builder)
    {
        $this->builders = [$name => $builder] + $this->builders;

        if (!isset($this->builders[$name])) {
            unset($this->builders[$name]);
        }

        return $this;
    }

    /**
     * Replace options
     *
     * @param array $merge
     * @return \AnourValar\EloquentRequest\Services\EloquentRequestService
     */
    public function options(array $merge)
    {
        $this->options = array_replace_recursive($this->options, $merge);

        return $this;
    }
}
