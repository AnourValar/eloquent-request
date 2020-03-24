<?php

namespace AnourValar\EloquentRequest\Console\Commands;

class ControllerMakeCommand extends \Illuminate\Routing\Console\ControllerMakeCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:controller-buildby';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new controller class [eloquent-request]';

    /**
     * {@inheritDoc}
     * @see \Illuminate\Routing\Console\ControllerMakeCommand::getStub()
     */
    protected function getStub()
    {
        return __DIR__.'/../../resources/controller.plain.stub';
    }
}
