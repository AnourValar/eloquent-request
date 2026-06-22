<?php

namespace AnourValar\EloquentRequest\Tests\Support;

class BareController
{
    use \AnourValar\EloquentRequest\ControllerTrait;

    /**
     * No usable profile (null) and no profile() method
     *
     * @var array|null
     */
    protected ?array $profile = null;

    /**
     * @param mixed $query
     * @return mixed
     */
    public function run($query)
    {
        return $this->buildBy($query);
    }
}
