<?php

namespace AnourValar\EloquentRequest\Builders;

use AnourValar\EloquentRequest\Validators\ValidatorInterface;
use Illuminate\Database\Eloquent\Builder;

class SortBuilder extends AbstractBuilder
{
    /**
     * @var array
     */
    protected $directions = ['ASC', 'DESC'];

    /**
     * {@inheritDoc}
     * @see \AnourValar\EloquentRequest\Builders\BuilderInterface::build()
     */
    public function build(Builder &$query, array $profile, array $request, array $config, ValidatorInterface &$validator): array
    {
        parent::build($query, $profile, $request, $config, $validator);
        $buildRequest = [];

        foreach ((array) optional($request)[$config['sort_key']] as $field => $value) {
            if (is_numeric($field)) {
                continue;
            }

            $buildRequest[$config['sort_key']][$field] = $value;
            $this->applySort($query, $field, $value);
        }

        return $buildRequest;
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $field
     * @param mixed $value
     * @return void
     */
    private function applySort(Builder &$query, string $field, $value): void
    {
        $key = $this->config['sort_key'];

        // Described in profile?
        $parsedField = $this->parseField(array_flip($this->profile[$key]), $field);
        if (! $parsedField) {
            $this->validator->addError(
                [$key, $field],
                trans('eloquent-request::validation.sort_not_supported', ['attribute' => $field])
            );

            return;
        }

        // Correct sort?
        if (is_string($value)) {
            $value = mb_strtoupper($value);
        }

        if (! in_array($value, $this->directions, true)) {
            $this->validator->addError(
                [$key, $field],
                trans(
                    'eloquent-request::validation.sort_not_exists',
                    ['attribute' => $this->getDisplayAttribute($query, [$field, $parsedField], $this->profile)]
                )
            );

            return;
        }

        // Apply
        $query->orderBy($this->getColumnFullname($query, $field), $value);
    }
}
