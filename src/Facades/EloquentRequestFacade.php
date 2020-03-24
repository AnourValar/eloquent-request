<?php

namespace AnourValar\EloquentRequest\Facades;

use Illuminate\Support\Facades\Facade;

class EloquentRequestFacade extends Facade
{
    /**
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return \AnourValar\EloquentRequest\Service::class;
    }
}
