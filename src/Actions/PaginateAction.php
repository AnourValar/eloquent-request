<?php

namespace AnourValar\EloquentRequest\Actions;

use AnourValar\EloquentRequest\Helpers\Fail;
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
    const OPTION_PAGE_OVER_LAST = 'action.paginate.page_over_last';

    /**
     * @var string
     */
    const OPTION_PAGE_MAX = 'action.paginate.page_max';

    /**
     * @var integer
     */
    protected const DEFAULT_PER_PAGE = 15;

    /**
     * @var integer
     */
    protected const DEFAULT_PAGE = 1;

    /**
     * {@inheritDoc}
     * @see \AnourValar\EloquentRequest\Actions\ActionInterface::passes()
     */
    public function passes(array $profile, array $request, array $config) : bool
    {
        return true;
    }

    /**
     * {@inheritDoc}
     * @see \AnourValar\EloquentRequest\Actions\ActionInterface::validate()
     */
    public function validate(array $profile, array $request, array $config, \Closure $fail) : ?Fail
    {
        // per page
        $keyPerPage = $config['per_page_key'];
        if (isset($request[$keyPerPage]) && !filter_var($request[$keyPerPage], FILTER_VALIDATE_INT)) {
            return $fail('eloquent-request::validation.per_page');
        }

        // page
        $keyPage = $config['page_key'];
        if (isset($request[$keyPage]) && !filter_var($request[$keyPage], FILTER_VALIDATE_INT)) {
            return $fail('eloquent-request::validation.page');
        }

        $page = $request[$config['page_key']] ?? static::DEFAULT_PAGE;
        $pageOverMax = $profile['options'][self::OPTION_PAGE_MAX] ?? null;
        if ($pageOverMax && $page > $pageOverMax) {
            return $fail('eloquent-request::validation.page_over_max', ['max' => $pageOverMax]);
        }

        return null;
    }

    /**
     * {@inheritDoc}
     * @see \AnourValar\EloquentRequest\Actions\ActionInterface::action()
     */
    public function action(Builder &$query, array $profile, array $request, array $config, \Closure $fail)
    {
        $perPage = $request[$config['per_page_key']] ?? static::DEFAULT_PER_PAGE;
        $page = $request[$config['page_key']] ?? static::DEFAULT_PAGE;

        if (in_array(self::OPTION_SIMPLE_PAGINATE, $profile['options'])) {
            $collection = $query->simplePaginate($perPage, ['*'], $config['page_key'], $page);
        } else {
            $collection = $query->paginate($perPage, ['*'], $config['page_key'], $page);
        }

        if (in_array(self::OPTION_PAGE_OVER_LAST, $profile['options']) && $page > 1 && !$collection->count()) {
            return $fail('eloquent-request::validation.page_over_last');
        }

        return $collection;
    }
}
