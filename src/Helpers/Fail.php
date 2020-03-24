<?php

namespace AnourValar\EloquentRequest\Helpers;

class Fail
{
    /**
     * @var string
     */
    private $message;

    /**
     * @var array
     */
    private $params;

    /**
     * Setters
     *
     * @param string $message
     * @param array $params
     */
    public function __construct(string $message, array $params)
    {
        $this->message = $message;
        $this->params = $params;
    }

    /**
     * @return string
     */
    public function message() : string
    {
        return $this->message;
    }

    /**
     * @param array $presets
     * @return array
     */
    public function params(array $presets = []) : array
    {
        return array_replace($presets, $this->params);
    }
}
