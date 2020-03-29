<?php

namespace AnourValar\EloquentRequest\Builders\Operations;

class SearchOperation extends LikeOperation
{
    /**
     * {@inheritDoc}
     * @see \AnourValar\EloquentRequest\Builders\Operations\LikeOperation::canonizeValue()
     */
    protected function canonizeValue($value) : string
    {
        return \App::make(\AnourValar\EloquentRequest\SearchService::class)->prepare($value);
    }
}
