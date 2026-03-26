<?php

namespace AnourValar\EloquentRequest\Facades;

use Illuminate\Support\Facades\Facade;

class EloquentRequestFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return \AnourValar\EloquentRequest\Service::class;
    }
}
