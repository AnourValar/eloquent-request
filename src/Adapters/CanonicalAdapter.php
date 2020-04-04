<?php

namespace AnourValar\EloquentRequest\Adapters;

class CanonicalAdapter implements AdapterInterface
{
    /**
     * {@inheritDoc}
     * @see \AnourValar\EloquentRequest\Adapters\AdapterInterface::canonize()
     */
    public function canonize(array $request, array $profile, array $config) : array
    {
        return $request;
    }
}
