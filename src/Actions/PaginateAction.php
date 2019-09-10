<?php

namespace AnourValar\EloquentRequest\Actions;

class PaginateAction
{
    /**
     * @var integer
     */
    protected const DEFAULT_PER_PAGE = 15;

    /**
     * Pagination
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $profile
     * @param array $request
     * @param array $options
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public static function act(
        \Illuminate\Database\Eloquent\Builder $query,
        array $profile,
        array $request,
        array $options
    ) {
        $keyPerPage = $options['per_page_key'];

        $perPage = static::DEFAULT_PER_PAGE;
        if (isset($request[$keyPerPage]) && is_numeric($request[$keyPerPage]) && $request[$keyPerPage] > 0) {
            $perPage = (int)$request[$keyPerPage];
        }

        return $query->paginate($perPage, ['*'], $options['page_key'], static::getPage($profile, $request, $options));
    }

    /**
     * @param array $profile
     * @param array $request
     * @param array $options
     * @return integer
     */
    private static function getPage(array $profile, array $request, array $options)
    {
        $page = $request[$options['page_key']] ?? null;

        if (filter_var($page, FILTER_VALIDATE_INT) !== false && (int) $page >= 1) {
            return (int) $page;
        }

        return 1;
    }
}
