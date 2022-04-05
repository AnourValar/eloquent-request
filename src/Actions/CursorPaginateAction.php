<?php

namespace AnourValar\EloquentRequest\Actions;

use Illuminate\Database\Eloquent\Builder;

class CursorPaginateAction implements ActionInterface
{
    /**
     * @var string
     */
    public const OPTION_APPLY = 'action.cursor_paginate.apply';

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
        return in_array(self::OPTION_APPLY, $profile['options']);
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
    }

    /**
     * {@inheritDoc}
     * @see \AnourValar\EloquentRequest\Actions\ActionInterface::action()
     */
    public function action(Builder &$query, array $profile, array $request, array $config, \Closure $fail)
    {
        $perPage = $request[$config['per_page_key']] ?? null;
        $cursor = $request[$config['cursor_key']] ?? null;

        $hasCursor = false;
        if (! is_null($cursor)) {
            $cursor = \Illuminate\Pagination\Cursor::fromEncoded($cursor);
            $hasCursor = true;
        }

        try {
            $collection = $query->cursorPaginate($perPage, ['*'], $config['cursor_key'], $cursor);
        } catch (\UnexpectedValueException $e) {
            $fail('eloquent-request::validation.cursor_paginate_incorrect', [], $config['cursor_key']);
        }

        if ($hasCursor && (!$collection->count() || !$cursor)) {
            $fail('eloquent-request::validation.cursor_paginate_incorrect', [], $config['cursor_key']);
        }

        return $collection;
    }
}
