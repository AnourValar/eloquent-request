<?php

namespace AnourValar\EloquentRequest\Facades;

use Illuminate\Support\Facades\Facade;

class EloquentRequestFlatFacade extends Facade
{
    /**
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return \AnourValar\EloquentRequest\FlatService::class;
    }
}
