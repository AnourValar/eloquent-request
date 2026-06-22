<?php

namespace AnourValar\EloquentRequest\Tests\Support;

class PropertyController
{
    use \AnourValar\EloquentRequest\ControllerTrait;

    /**
     * @var array|null
     */
    protected ?array $profile = [
        'filter' => ['status' => ['=']],
        'sort' => ['id'],
    ];

    /**
     * @param mixed $query
     * @param array|null $profile
     * @param array|null $request
     * @return mixed
     */
    public function run($query, ?array $profile = null, ?array $request = null)
    {
        return $this->buildBy($query, $profile, $request);
    }

    /**
     * @return \AnourValar\EloquentRequest\Helpers\Request
     */
    public function last(): \AnourValar\EloquentRequest\Helpers\Request
    {
        return $this->getBuildRequest();
    }

    /**
     * @return void
     */
    public function resetLast(): void
    {
        $this->lastBuildRequest = null;
    }
}
