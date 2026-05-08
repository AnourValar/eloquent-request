<?php

namespace AnourValar\EloquentRequest\Tests;

abstract class AbstractSuite extends \Orchestra\Testbench\TestCase
{
    /**
     * Init
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function getPackageProviders($app)
    {
        return [
            \AnourValar\EloquentRequest\Providers\EloquentRequestServiceProvider::class,
            \AnourValar\LaravelAtom\Providers\LaravelAtomServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app)
    {
        return [
            'EloquentRequestSearch' => \AnourValar\EloquentRequest\Facades\EloquentRequestSearchFacade::class,
        ];
    }
}
