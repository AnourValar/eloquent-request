<?php

namespace AnourValar\EloquentRequest\Actions;

use Illuminate\Database\Eloquent\Builder;

class PaginateAction implements ActionInterface
{
    /**
     * @var string
     */
    const OPTION_SIMPLE_PAGINATE = 'action.paginate.simple';

    /**
     * @var string
     */
    const OPTION_PAGE_OVER_LAST_IS_FORBIDDEN = 'action.paginate.page_over_last_is_forbidden';

    /**
     * @var string
     */
    const OPTION_PAGE_MAX = 'action.paginate.page_max';

    /**
     * @var integer
     */
    protected const MAX_PER_PAGE = 2000;

    /**
     * @var integer
     */
    protected const DEFAULT_PAGE = 1;

    /**
     * {@inheritDoc}
     * @see \AnourValar\EloquentRequest\Actions\ActionInterface::passes()
     */
    public function passes(array $profile, array $request, array $config): bool
    {
        return true;
    }

    /**
     * {@inheritDoc}
     * @see \AnourValar\EloquentRequest\Actions\ActionInterface::validate()
     */
    public function validate(array $profile, array $request, array $config, \Closure $fail): void
    {
        // per page
        $keyPerPage = $config['per_page_key'];
        $perPage = $request[$keyPerPage] ?? 1;

        if (!filter_var($perPage, FILTER_VALIDATE_INT) || $perPage < 1) {
            $fail('eloquent-request::validation.per_page', [], $keyPerPage);
        }

        if (static::MAX_PER_PAGE && $perPage > static::MAX_PER_PAGE) {
            $fail('eloquent-request::validation.per_page_over_max', ['max' => static::MAX_PER_PAGE], $keyPerPage);
        }


        // page
        $keyPage = $config['page_key'];
        $page = $request[$keyPage] ?? static::DEFAULT_PAGE;

        if (!filter_var($page, FILTER_VALIDATE_INT) || $page < 1) {
            $fail('eloquent-request::validation.page', [], $keyPage);
        }

        $pageOverMax = $profile['options'][self::OPTION_PAGE_MAX] ?? null;
        if ($pageOverMax && $page > $pageOverMax) {
            $fail('eloquent-request::validation.page_over_max', ['max' => $pageOverMax], $keyPage);
        }
    }

    /**
     * {@inheritDoc}
     * @see \AnourValar\EloquentRequest\Actions\ActionInterface::action()
     */
    public function action(Builder &$query, array $profile, array $request, array $config, \Closure $fail)
    {
        $perPage = $request[$config['per_page_key']] ?? null;
        $page = $request[$config['page_key']] ?? static::DEFAULT_PAGE;

        if (in_array(self::OPTION_SIMPLE_PAGINATE, $profile['options'])) {
            $collection = $query->simplePaginate($perPage, ['*'], $config['page_key'], $page);
        } else {
            $collection = $query->paginate($perPage, ['*'], $config['page_key'], $page);
        }

        if (in_array(self::OPTION_PAGE_OVER_LAST_IS_FORBIDDEN, $profile['options']) && $page > 1 && !$collection->count()) {
            $fail('eloquent-request::validation.page_over_last_is_forbidden', [], $config['page_key']);
        }

        return $collection;
    }
}
