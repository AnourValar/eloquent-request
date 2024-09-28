<?php

namespace AnourValar\EloquentRequest\Builders\Operations;

class JsonInOperation extends InOperation
{
    /**
     * Converation: "column"->'key' @> '[1]' => "column" @> '["key": [1]]'
     *
     * @var string
     */
    public const OPTION_JSON_PATH_TO_STRUCTURE = 'builder.operation.json.path_to_structure';

    /**
     * @var int
     */
    protected const MAX_JSON_COUNT = 100;

    /**
     * @var int
     */
    protected const MAX_JSON_LENGTH = 3000;

    /**
     * {@inheritDoc}
     * @see \AnourValar\EloquentRequest\Builders\Operations\OperationInterface::validate()
     */
    public function validate($value, \Closure $fail): void
    {
        if (! is_array($value)) {
            $fail('eloquent-request::validation.list');
        }

        if (count($value) > static::MAX_JSON_COUNT) {
            $fail('eloquent-request::validation.list');
        }

        if (mb_strlen(json_encode($value, JSON_UNESCAPED_UNICODE)) > static::MAX_JSON_LENGTH) {
            $fail('eloquent-request::validation.length');
        }
    }

    /**
     * {@inheritDoc}
     * @see \AnourValar\EloquentRequest\Builders\Operations\OperationInterface::apply()
     */
    public function apply(\Illuminate\Database\Eloquent\Builder &$query, string $field, $value, array $options): void
    {
        $query->where(function ($query) use ($field, $value, $options) {
            $originalField = $field;
            foreach ($this->getNullableList($value) as $item) {
                $field = $originalField;
                $this->convertOperands($field, $item, $options);

                $query->orWhereJsonContains($field, $item); // @TODO: @?? 'lax $[*] $ <...>' ?
            }
        });
    }

    /**
     * @param array $value
     * @return array
     */
    protected function getNullableList(array $value): array
    {
        $nullable = false;
        $hasNull = false;

        foreach ($value as &$item) {
            if ($item === '' || $item === 0 || $item === '0') {
                $nullable = true;
            }

            if (is_null($item)) {
                $hasNull = true;
            }
        }
        unset($item);

        if ($nullable && ! $hasNull) {
            $value[] = null;
        }
        $value = array_unique($value);

        return $value;
    }

    /**
     * @param string $field
     * @param mixed $item
     * @param array $options
     */
    protected function convertOperands(string &$field, &$item, array $options): void
    {
        if (! in_array(self::OPTION_JSON_PATH_TO_STRUCTURE, $options)) {
            return;
        }

        $elements = explode('->', $field);
        if (! isset($elements[1])) {
            return;
        }

        $field = array_shift($elements);
        foreach (array_reverse($elements) as $element) {
            if ($element == '*') {
                $item = [$item];
            } else {
                $item = [$element => $item];
            }
        }
    }
}
