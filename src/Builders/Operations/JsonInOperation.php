<?php

namespace AnourValar\EloquentRequest\Builders\Operations;

class JsonInOperation extends InOperation
{
    /**
     * Converation: "column"->'key' @> '[1]' => "column" @> '["key": [1]]'
     *
     * @var string
     */
    const OPTION_JSON_PATH_TO_STRUCTURE = 'builder.operation.json.path_to_structure';

    /**
     * @var integer
     */
    protected const MAX_LENGTH = 100;

    /**
     * @var integer
     */
    protected const MAX_COUNT = 100;

    /**
     * {@inheritDoc}
     * @see \AnourValar\EloquentRequest\Builders\Operations\OperationInterface::apply()
     */
    public function apply(\Illuminate\Database\Eloquent\Builder &$query, string $field, $value, array $options): void
    {
        $query->where(function ($query) use ($field, $value, $options)
        {
            $originalField = $field;
            foreach ($value as $item) {
                $field = $originalField;
                $this->convertOperands($field, $item, $options);

                $query->orWhereJsonContains($field, $item);
            }
        });
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
        $item = (array) $item;
        foreach (array_reverse($elements) as $element) {
            $item = [$element => $item];
        }
    }
}
