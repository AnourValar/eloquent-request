<?php

namespace AnourValar\EloquentRequest;

trait ControllerTrait
{
    /**
     * Presets of availables operations
     *
     * @var array
     */
    protected const PROFILE_FILTER_ID = ['=', 'in']; // '=', '!=', 'in', 'not-in'
    protected const PROFILE_FILTER_BOOLEAN = ['=', 'in']; // '=', '!=', 'in', 'not-in'
    protected const PROFILE_FILTER_NUMBER = ['=', '<', '<=', '>', '>=', 'in']; // '=', '!=', '<', '<=', '>', '>=', 'in', 'not-in'
    protected const PROFILE_FILTER_DATE = ['=', '<', '<=', '>', '>=', 'in']; // '=', '!=', '<', '<=', '>', '>=', 'in', 'not-in'
    protected const PROFILE_FILTER_TEXT = ['=', 'like']; // '=', '!=', 'like', 'not-like'
    protected const PROFILE_FILTER_IS_NULL = ['is-null' => 'is-null']; // 'is-null' => 'is-null'
    protected const PROFILE_FILTER_SEARCH = ['search']; // 'search'
    protected const PROFILE_FILTER_JSON = ['json-in', 'json-contains']; // 'json-in', 'json-contains', 'json-not-in', 'json-not-contains'

    /**
     * Presets of availables ranges
     *
     * @var int
     */
    protected const PROFILE_RANGE_TINYINT = 127;
    protected const PROFILE_RANGE_UNSIGNED_TINYINT = 255; // MySQL

    protected const PROFILE_RANGE_SMALLINT = 32767;
    protected const PROFILE_RANGE_UNSIGNED_SMALLINT = 65535; // MySQL

    protected const PROFILE_RANGE_MEDIUMINT = 8388607;
    protected const PROFILE_RANGE_UNSIGNED_MEDIUMINT = 16777215; // MySQL

    protected const PROFILE_RANGE_INT = 2147483647;
    protected const PROFILE_RANGE_UNSIGNED_INT = 4294967295; // MySQL

    /**
     * @var \AnourValar\EloquentRequest\Helpers\Request|null
     */
    private ?\AnourValar\EloquentRequest\Helpers\Request $lastBuildRequest;

    /**
     * @see \AnourValar\EloquentRequest\Service::buildBy()
     *
     * @param mixed $query
     * @param array|null $profile
     * @param array|null $request
     * @param mixed $buildRequest
     * @param callable|null $handler
     * @return mixed
     */
    protected function buildBy($query, ?array $profile = null, ?array $request = null, &$buildRequest = null, ?callable $handler = null)
    {
        $this->buildingContext($profile, $request);

        $result = \App::make(\AnourValar\EloquentRequest\Service::class)->buildBy($query, $profile, $request, $buildRequest, $handler);
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
     * @param array|null $profile
     * @param array|null $request
     * @throws \LogicException
     * @return void
     */
    private function buildingContext(?array &$profile = null, ?array &$request = null): void
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
