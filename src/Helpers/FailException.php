<?php

namespace AnourValar\EloquentRequest\Helpers;

class FailException extends \Exception
{
    /**
     * @var array
     */
    private $params;

    /**
     * @var string|null
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
    public function getParams(array $presets = []): array
    {
        return array_replace($presets, $this->params);
    }

    /**
     * @param string $default
     * @return string|null
     */
    public function getSuffix(string $default = null): ?string
    {
        return $this->suffix ?? $default;
    }
}
