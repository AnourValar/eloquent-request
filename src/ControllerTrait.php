<?php

namespace AnourValar\EloquentRequest;

trait ControllerTrait
{
    /**
     * @var \AnourValar\EloquentRequest\Helpers\Request|null
     */
    private ?\AnourValar\EloquentRequest\Helpers\Request $lastBuildRequest;

    /**
     * @see \AnourValar\EloquentRequest\Service::buildBy()
     *
     * @param mixed $query
     * @param array $profile
     * @param array $request
     * @param mixed $buildRequest
     * @return mixed
     */
    protected function buildBy($query, array $profile = null, array $request = null, &$buildRequest = null)
    {
        $this->buildingContext($profile, $request);

        $result = \App::make(\AnourValar\EloquentRequest\Service::class)->buildBy($query, $profile, $request, $buildRequest);
        $this->lastBuildRequest = $buildRequest;
        return $result;
    }

    /**
     * Get fact request data
     *
     * @throws \RuntimeException
     * @return \AnourValar\EloquentRequest\Helpers\Request
     */
    protected function getBuildRequest(): \AnourValar\EloquentRequest\Helpers\Request
    {
        if (! $this->lastBuildRequest) {
            throw new \RuntimeException('Incorrect usage.');
        }

        return $this->lastBuildRequest;
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
        if (is_null($profile) && method_exists($this, 'profile')) {
            $profile = $this->profile();
        }
        if (is_null($profile) && isset($this->profile)) {
            $profile = $this->profile;
        }
        if (is_null($profile)) {
            throw new \LogicException('Profile cannot be null.');
        }

        // Request
        if (is_null($request)) {
            $request = request()->input();
        }
    }
}
