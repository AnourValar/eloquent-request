<?php

namespace AnourValar\EloquentRequest;

class SearchService
{
    /**
     * Keyboard layout (typo)
     *
     * @param string|null $value
     * @param string $inputLocale
     * @param string $outputLocale
     * @return string|null
     */
    public function typo(?string $value, string $inputLocale, string $outputLocale): ?string
    {
        return $this->replace(
            $value,
            config("eloquent_request.typo.$inputLocale"),
            config("eloquent_request.typo.$outputLocale")
        );
    }

    /**
     * Convert similar letters
     *
     * @param string|null $value
     * @param string $inputLocale
     * @param string $outputLocale
     * @return string
     */
    public function similar(?string $value, string $inputLocale, string $outputLocale): ?string
    {
        return $this->replace(
            $value,
            config("eloquent_request.similar.$inputLocale"),
            config("eloquent_request.similar.$outputLocale")
        );
    }

    /**
     * Generates search string for storing
     *
     * @param array|null $values
     * @return string|null
     */
    public function generate(?array $values): ?string
    {
        $replacers = config('eloquent_request.replacers');
        $list = [];

        foreach ((array) $values as $value) {
            if (is_null($value)) {
                continue;
            }

            $value = mb_strtolower($value);
            $value = strtr($value, $replacers);
            $value = explode(' ', $value);
            $value = array_map('trim', $value);

            $list = array_merge($list, $value);
        }

        if (! $list) {
            return null;
        }

        $list = array_unique($list);
        sort($list);

        return ' ' . trim(implode(' ', $list)) . ' ';
    }

    /**
     * Prepares string for searching
     *
     * @param string $value
     * @return string
     */
    public function prepare(string $value): string
    {
        $value = trim($this->generate([$value]));
        $value = addcslashes($value, '_%\\');

        return '%'.str_replace(' ', '%', $value).'%';
    }

    /**
     * @param string $value
     * @param array $inputRules
     * @param array $outputRules
     * @return string|null
     */
    private function replace(?string $value, array $inputRules, array $outputRules): ?string
    {
        if (is_null($value)) {
            return $value;
        }

        $value = strtr($value, config('eloquent_request.replacers'));

        $trans = str_replace($inputRules['first'], $outputRules['first'], $value, $count);
        if ($count) {
            $value = str_replace($inputRules['second'], $outputRules['second'], $trans);
        }

        return $value;
    }
}
