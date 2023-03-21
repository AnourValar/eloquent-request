<?php

namespace AnourValar\EloquentRequest\Adapters;

class CanonicalAdapter implements AdapterInterface
{
    /**
     * {@inheritDoc}
     * @see \AnourValar\EloquentRequest\Adapters\AdapterInterface::canonize()
     */
    public function canonize(array $request, array $profile, array $config): array
    {
        if (isset($request['eloquent_request']) && is_string($request['eloquent_request'])) {
            return json_decode($request['eloquent_request'], true) ?? [];
        }

        return $request;
    }
}
