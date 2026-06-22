<?php

namespace AnourValar\EloquentRequest\Tests\Support;

class EloquentSerializeFake
{
    /**
     * Deterministic "serialization" of a query (enough for cache-key tests)
     *
     * @param \Illuminate\Database\Eloquent\Builder $builder
     * @return string
     */
    public function serialize($builder): string
    {
        return json_encode([
            'sql' => $builder->toSql(),
            'bindings' => $builder->getBindings(),
        ]);
    }
}
