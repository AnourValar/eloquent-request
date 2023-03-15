<?php

namespace AnourValar\EloquentRequest\Helpers;

use Illuminate\Database\Eloquent\Builder;

trait ValidationTrait
{
    /**
     * Get display name of the attribute
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param mixed $fields
     * @param array $profile
     * @return string
     */
    protected function getDisplayAttribute(Builder $query, $fields, array $profile = []): string
    {
        $result = [];

        foreach ((array) $fields as $field) {
            $result[] = $this->parseDisplayAttribute($query, $field, $profile);
        }

        usort(
            $result,
            function ($a, $b) {
                return $b['weight'] <=> $a['weight'];
            }
        );

        return array_shift($result)['value'];
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $field
     * @param array $profile
     * @return array
     */
    private function parseDisplayAttribute(Builder $query, string $field, array $profile): array
    {
        $relations = explode('.', $field);
        $attribute = str_replace('->', '.', array_pop($relations));
        $field = str_replace('->', '.', $field);

        // From profile
        if ($profile['custom_attributes']) {
            if (isset($profile['custom_attributes'][$field])) {
                return ['weight' => 5, 'value' => $profile['custom_attributes'][$field]];
            }
        }

        if ($profile['custom_attributes_path']) {
            $customAttributes = (array) trans($profile['custom_attributes_path']);

            if (isset($customAttributes[$field])) {
                return ['weight' => 4, 'value' => $customAttributes[$field]];
            }
        }

        if ($profile['custom_attributes_handler']) {
            $value = $profile['custom_attributes_handler']($field);

            if (isset($value)) {
                return ['weight' => 3, 'value' => $value];
            }
        }

        // From model
        foreach ($relations as $relation) {
            $query = $query->getModel()->$relation();
        }
        $query = $query->getModel();

        if (method_exists($query, 'getAttributeNames')) {
            $attributes = $query->getAttributeNames();

            if (isset($attributes[$attribute])) {
                return ['weight' => 2, 'value' => $attributes[$attribute]];
            }
        }

        // Custom Validation Attributes
        $attributes = trans('validation.attributes');
        if (isset($attributes[$attribute])) {
            return ['weight' => 1, 'value' => $attributes[$attribute]];
        }

        // As is
        return ['weight' => 0, 'value' => $attribute];
    }

    /**
     * Get closure function for validation
     *
     * @return \Closure
     */
    protected function getFailClosure(): \Closure
    {
        return function (string $message, array $params = [], string $suffix = null) {
            throw new \AnourValar\EloquentRequest\Helpers\FailException($message, $params, $suffix);
        };
    }
}
