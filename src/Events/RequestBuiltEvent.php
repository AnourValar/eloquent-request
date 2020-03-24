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
     * @var array
     */
    public $config;

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
     * @param array $config
     * @param string $actionName
     * @return void
     */
    public function __construct(object $collection, array $profile, array $request, array $config, string $actionName)
    {
        $this->collection = $collection;
        $this->profile = $profile;
        $this->request = $request;
        $this->config = $config;
        $this->actionName = $actionName;
    }
}
