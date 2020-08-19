<?php

namespace AnourValar\EloquentRequest\Builders;

use Illuminate\Database\Eloquent\Builder;
use AnourValar\EloquentRequest\Validators\ValidatorInterface;

abstract class AbstractBuilder implements BuilderInterface
{
    use \AnourValar\EloquentRequest\Helpers\ValidationTrait;

    /**
     * @var array
     */
    protected $profile;

    /**
     * @var array
     */
    protected $request;

    /**
     * @var array
     */
    protected $config;

    /**
     * @var \AnourValar\EloquentRequest\Validators\ValidatorInterface
     */
    protected $validator;

    /**
     * {@inheritDoc}
     * @see \AnourValar\EloquentRequest\Builders\BuilderInterface::build()
     */
    public function build(Builder &$query, array $profile, array $request, array $config, ValidatorInterface &$validator): void
    {
        $this->profile = $profile;
        $this->request = $request;
        $this->config = $config;
        $this->validator = &$validator;
    }
}
