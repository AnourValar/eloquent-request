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
     * @param array $profile
     * @return string
     */
    protected function getDisplayAttribute(Builder $query, string $field, array $profile) : string
    {
        // From profile
        if ($profile['custom_attributes_path']) {
            $customAttributes = (array)trans($profile['custom_attributes_path']);

            if (isset($customAttributes[$field])) {
                return $customAttributes[$field];
            }
        }

        // From model
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

        // As is
        return $attribute;
    }

    /**
     * Get closure function for validation
     *
     * @return \Closure
     */
    protected function getFailClosure() : \Closure
    {
        return function (string $message, array $params = [], string $suffix = null)
        {
            throw new \AnourValar\EloquentRequest\Helpers\FailException($message, $params, $suffix);
        };
    }
}
