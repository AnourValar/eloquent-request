<?php

namespace AnourValar\EloquentRequest\Actions;

use Illuminate\Database\Eloquent\Builder;

class PaginateAction implements ActionInterface
{
    /**
     * @var string
     */
    public const OPTION_SIMPLE_PAGINATE = 'action.paginate.simple';

    /**
     * @var string
     */
    public const OPTION_PAGE_OVER_LAST_IS_FORBIDDEN = 'action.paginate.page_over_last_is_forbidden';

    /**
     * @var string
     */
    public const OPTION_PAGE_MAX = 'action.paginate.page_max';

    /**
     * @var int
     */
    protected const MAX_PER_PAGE = 2000;

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
        $perPage = $request[$keyPerPage];

        if (! filter_var($perPage, FILTER_VALIDATE_INT) || $perPage < 1) {
            $fail('eloquent-request::validation.per_page', [], $keyPerPage);
        }

        if (static::MAX_PER_PAGE && $perPage > static::MAX_PER_PAGE) {
            $fail('eloquent-request::validation.per_page_over_max', ['max' => static::MAX_PER_PAGE], $keyPerPage);
        }


        // page
        $keyPage = $config['page_key'];
        $page = $request[$keyPage];

        if (! filter_var($page, FILTER_VALIDATE_INT) || $page < 1) {
            $fail('eloquent-request::validation.page', [], $keyPage);
        }

        $pageOverMax = $profile['options'][self::OPTION_PAGE_MAX] ?? null;
        if ($pageOverMax && $page > $pageOverMax) {
            $fail('eloquent-request::validation.page_over_max', ['max' => $pageOverMax], $keyPage);
        }
    }

    /**
     * {@inheritDoc}
     * @see \AnourValar\EloquentRequest\Actions\ActionInterface::requestParameters()
     */
    public function requestParameters(array $profile, array $request, array $config): array
    {
        return [
            $config['per_page_key'] => $request[$config['per_page_key']],
            $config['page_key'] => $request[$config['page_key']],
        ];
    }

    /**
     * {@inheritDoc}
     * @see \AnourValar\EloquentRequest\Actions\ActionInterface::action()
     */
    public function action(Builder &$query, array $profile, array $requestParameters, array $config, \Closure $fail)
    {
        $perPage = $requestParameters[$config['per_page_key']];
        $page = $requestParameters[$config['page_key']];

        if (in_array(self::OPTION_SIMPLE_PAGINATE, $profile['options'])) {
            $collection = $query->simplePaginate($perPage, ['*'], $config['page_key'], $page);
        } else {
            $collection = $query->paginate($perPage, ['*'], $config['page_key'], $page);
        }

        if (in_array(self::OPTION_PAGE_OVER_LAST_IS_FORBIDDEN, $profile['options']) && $page > 1 && ! $collection->count()) {
            $fail('eloquent-request::validation.page_over_last_is_forbidden', [], $config['page_key']);
        }

        return $collection;
    }
}
