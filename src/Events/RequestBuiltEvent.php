<?php

namespace AnourValar\EloquentRequest\Events;

class RequestBuiltEvent
{
    /**
     * @var mixed
     */
    public $result;

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
     * @param mixed $result
     * @param array $profile
     * @param array $request
     * @param array $config
     * @param string $actionName
     * @return void
     */
    public function __construct($result, array $profile, array $request, array $config, string $actionName)
    {
        $this->result = $result;
        $this->profile = $profile;
        $this->request = $request;
        $this->config = $config;
        $this->actionName = $actionName;
    }
}
