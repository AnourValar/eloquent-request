<?php

namespace AnourValar\EloquentRequest\Builders\Operations;

class SearchOperation extends LikeOperation
{
    /**
     * {@inheritDoc}
     * @see \AnourValar\EloquentRequest\Builders\Operations\LikeOperation::validate()
     */
    public function validate($value, \Closure $fail): void
    {
        if (is_string($value)) {
            $value = trim($value);
        }

        parent::validate($value, $fail);
    }

    /**
     * {@inheritDoc}
     * @see \AnourValar\EloquentRequest\Builders\Operations\LikeOperation::canonizeValue()
     */
    protected function canonizeValue($value): string
    {
        return \App::make(\AnourValar\EloquentRequest\SearchService::class)->prepare($value);
    }
}
