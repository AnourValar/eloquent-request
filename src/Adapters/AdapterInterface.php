<?php

namespace AnourValar\EloquentRequest\Adapters;

interface AdapterInterface
{
    /**
     * Convert input (raw) request data
     *
     * @param array $request
     * @return array
     */
    public function canonize(array $request) : array;
}
