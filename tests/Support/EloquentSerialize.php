<?php

namespace AnourValar\EloquentRequest\Tests\Support;

use Illuminate\Support\Facades\Facade;

class EloquentSerialize extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return EloquentSerializeFake::class;
    }
}
