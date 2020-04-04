<?php

namespace AnourValar\EloquentRequest\Builders;

use Illuminate\Database\Eloquent\Builder;
use AnourValar\EloquentRequest\Helpers\Validator;

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
     * @var \AnourValar\EloquentRequest\Helpers\Validator
     */
    protected $validator;

    /**
     * {@inheritDoc}
     * @see \AnourValar\EloquentRequest\Builders\BuilderInterface::build()
     */
    public function build(Builder &$query, array $profile, array $request, array $config, Validator &$validator) : void
    {
        $this->profile = $profile;
        $this->request = $request;
        $this->config = $config;
        $this->validator = &$validator;
    }
}
