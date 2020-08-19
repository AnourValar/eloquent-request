<?php

namespace AnourValar\EloquentRequest\Builders;

use Illuminate\Database\Eloquent\Builder;
use AnourValar\EloquentRequest\Validators\ValidatorInterface;

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
    public function build(Builder &$query, array $profile, array $request, array $config, ValidatorInterface &$validator): void
    {
        parent::build($query, $profile, $request, $config, $validator);

        foreach ((array)optional($request)[$config['sort_key']] as $field => $value) {
            if (is_numeric($field)) {
                continue;
            }

            $this->applySort($query, $field, $value);
        }
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
        if (! in_array($field, $this->profile[$key])) {
            $this->validator->addError(
                [$key, $field],
                trans(
                    'eloquent-request::validation.sort_not_supported',
                    ['attribute' => $this->getDisplayAttribute($query, $field, $this->profile)]
                )
            );

            return;
        }

        // Correct sort?
        if (is_string($value)) {
            $value = mb_strtoupper($value);
        }

        if (! in_array($value, $this->directions)) {
            $this->validator->addError(
                [$key, $field],
                trans(
                    'eloquent-request::validation.sort_not_exists',
                    ['attribute' => $this->getDisplayAttribute($query, $field, $this->profile)]
                )
            );

            return;
        }

        // Apply
        $query->orderBy($field, $value);
    }
}
