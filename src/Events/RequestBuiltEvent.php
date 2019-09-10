<?php

namespace AnourValar\EloquentRequest\Events;

class RequestBuiltEvent
{
    /**
     * @var mixed
     */
    public $collection;

    /**
     * @var array
     */
    public $profile;

    /**
     * @var array
     */
    public $request;

    /**
     * @var string
     */
    public $actionName;

    /**
     * Create a new event instance.
     *
     * @param object $collection
     * @param array $profile
     * @param array $request
     * @param string $actionName
     * @return void
     */
    public function __construct(object $collection, array $profile, array $request, string $actionName)
    {
        $this->collection = $collection;
        $this->profile = $profile;
        $this->request = $request;
        $this->actionName = $actionName;
    }
}
