<?php

namespace AnourValar\EloquentRequest\Helpers;

use Illuminate\Database\Eloquent\Builder;

trait ValidationTrait
{
    /**
     * Get display name of attribute
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $field
     * @return string
     */
    protected function getDisplayAttribute(Builder $query, string $field) : string
    {
        $relations = explode('.', $field);
        $attribute = array_pop($relations);

        foreach ($relations as $relation) {
            $query = $query->getModel()->$relation();
        }
        $query = $query->getModel();

        if (method_exists($query, 'getAttributeNames')) {
            $attributes = $query->getAttributeNames();

            if (isset($attributes[$attribute])) {
                return $attributes[$attribute];
            }
        }

        return $attribute;
    }

    /**
     * Get closure function for validation
     *
     * @return \Closure
     */
    protected function getFailClosure() : \Closure
    {
        return function (string $message, array $params = [])
        {
            return new \AnourValar\EloquentRequest\Helpers\Fail($message, $params);
        };
    }
}
