<?php

namespace AnourValar\EloquentRequest\Builders\Operations;

use Illuminate\Database\Query\Expression;

class JsonInOperation extends InOperation
{
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
    public function apply(\Illuminate\Database\Eloquent\Builder &$query, string|Expression $field, $value, array $options): void
    {
        $query->where(function ($query) use ($field, $value) {
            foreach ($value as $item) { // array_unique...
                $query->orWhereJsonContains($field, $item); // @TODO: @?? 'lax $[*] $ <...>' ?
            }
        });
    }
}
