<?php

namespace AnourValar\EloquentRequest;

use \AnourValar\EloquentRequest\Services\EloquentRequestService;

trait ControllerTrait
{
    /**
     * @see \AnourValar\EloquentRequest\Services\EloquentRequestService@buildBy
     *
     * @param mixed $query
     * @param array $profile
     * @param array $request
     * @throws \Exception
     * @return \Illuminate\Support\Collection|\Illuminate\Pagination\LengthAwarePaginator|\Illuminate\Pagination\Paginator
     */
    protected function buildBy($query, array $profile = null, array $request = null)
    {
        // Profile
        if (is_null($profile) && isset($this->profile)) {
            $profile = $this->profile;
        }
        if (is_null($profile)) {
            throw new \Exception('Profile cannot be null');
        }

        // Request
        if (is_null($request)) {
            $request = \Request::input();
        }
        if (is_null($request)) {
            throw new \Exception('Request cannot be null');
        }

        return \App::make(EloquentRequestService::class)->buildBy($query, $profile, $request);
    }
}
