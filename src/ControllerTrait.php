<?php

namespace AnourValar\EloquentRequest;

trait ControllerTrait
{
    /**
     * @see \AnourValar\EloquentRequest\Service::buildBy()
     *
     * @param mixed $query
     * @param array $profile
     * @param array $request
     * @return mixed
     */
    protected function buildBy($query, array $profile = null, array $request = null)
    {
        $this->buildingContext($profile, $request);

        return \App::make(\AnourValar\EloquentRequest\Service::class)->buildBy($query, $profile, $request);
    }

    /**
     * @see \AnourValar\EloquentRequest\Service::getBuildRequest()
     *
     * @param array $profile
     * @param array $request
     * @return \AnourValar\EloquentRequest\Helpers\Request
     */
    protected function getBuildRequest(array $profile = null, array $request = null): \AnourValar\EloquentRequest\Helpers\Request
    {
        $this->buildingContext($profile, $request);

        return \App::make(\AnourValar\EloquentRequest\Service::class)->getBuildRequest($profile, $request);
    }

    /**
     * @param array $profile
     * @param array $request
     * @throws \LogicException
     * @return void
     */
    private function buildingContext(array &$profile = null, array &$request = null): void
    {
        // Profile
        if (is_null($profile) && isset($this->profile)) {
            $profile = $this->profile;
        }
        if (is_null($profile)) {
            throw new \LogicException('Profile cannot be null');
        }

        // Request
        if (is_null($request)) {
            $request = request()->input();
        }
    }
}
