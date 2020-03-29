<?php

namespace AnourValar\EloquentRequest;

class SearchService
{
    /**
     * Generates search string for storing
     *
     * @param array $values
     * @return string|null
     */
    public function generate(array $values) : ?string
    {
        $list = [];

        foreach ($values as $value) {
            if (is_null($value)) {
                continue;
            }

            $value = mb_strtolower($value);
            $value = explode(' ', $value);
            $value = array_map('trim', $value);

            $list = array_merge($list, $value);
        }

        if (! $list) {
            return null;
        }

        $list = array_unique($list);
        sort($list);

        return trim(implode(' ', $list));
    }

    /**
     * Prepares string for searching
     *
     * @param string $value
     * @return string
     */
    public function prepare(string $value) : string
    {
        $value = $this->generate([trim($value)]);
        $value = addCslashes($value, '_%\\');

        return '%'.str_replace(' ', '%', $value).'%';
    }
}
