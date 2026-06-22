<?php

namespace AnourValar\EloquentRequest\Tests\Support;

class MethodController
{
    use \AnourValar\EloquentRequest\ControllerTrait;

    /**
     * Resolved via the profile() method (this property stays null)
     *
     * @var array|null
     */
    protected ?array $profile = null;

    /**
     * Profile resolved via method()
     *
     * @return array
     */
    public function profile(): array
    {
        return [
            'filter' => ['status' => ['=']],
            'sort' => ['id'],
        ];
    }

    /**
     * @param mixed $query
     * @return mixed
     */
    public function run($query)
    {
        return $this->buildBy($query);
    }
}
