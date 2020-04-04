<?php

namespace AnourValar\EloquentRequest\Adapters;

interface AdapterInterface
{
    /**
     * Convert input (raw) request data
     *
     * @param array $request
     * @param array $profile
     * @param array $config
     * @return array
     */
    public function canonize(array $request, array $profile, array $config) : array;
}
