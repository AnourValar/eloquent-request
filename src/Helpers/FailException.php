<?php

namespace AnourValar\EloquentRequest\Helpers;

class FailException extends \Exception
{
    /**
     * @var array
     */
    private $params;

    /**
     * @var string|NULL
     */
    private $suffix;

    /**
     * Setters
     *
     * @param string $message
     * @param array $params
     * @param string $suffix
     * $return void
     */
    public function __construct(string $message, array $params, ?string $suffix)
    {
        parent::__construct($message);

        $this->params = $params;
        $this->suffix = $suffix;
    }

    /**
     * @param array $presets
     * @return array
     */
    public function getParams(array $presets = []) : array
    {
        return array_replace($presets, $this->params);
    }

    /**
     * @param string $prefix
     * @return string|NULL
     */
    public function getSuffix(string $prefix = null) : ?string
    {
        $suffix = $this->suffix;
        if (! is_null($suffix)) {
            $suffix = $prefix.$suffix;
        }

        return $suffix;
    }
}
