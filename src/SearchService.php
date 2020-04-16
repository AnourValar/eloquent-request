<?php

namespace AnourValar\EloquentRequest;

class SearchService
{
    /**
     * Keyboard layout (typo)
     *
     * @param string $value
     * @param string $typoLocale
     * @return string|NULL
     */
    public function typo(?string $value, string $typoLocale) : ?string
    {
        if (is_null($value)) {
            return $value;
        }

        $typoLocale = config("eloquent_request.typo.$typoLocale");
        if (! $typoLocale) {
            return null;
        }

        return str_replace($typoLocale['correct'], $typoLocale['incorrect'], mb_strtolower($value));
    }

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
