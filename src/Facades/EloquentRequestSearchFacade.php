<?php

namespace AnourValar\EloquentRequest\Facades;

use Illuminate\Support\Facades\Facade;

class EloquentRequestSearchFacade extends Facade
{
    /**
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return \AnourValar\EloquentRequest\SearchService::class;
    }
}
