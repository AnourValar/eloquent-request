<?php

namespace AnourValar\EloquentRequest\Builders;

use AnourValar\EloquentRequest\Validators\ValidatorInterface;
use Illuminate\Database\Eloquent\Builder;

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
    public function build(Builder &$query, array $profile, array $request, array $config, ValidatorInterface &$validator): array
    {
        $this->profile = $profile;
        $this->request = $request;
        $this->config = $config;
        $this->validator = &$validator;

        return [];
    }

    /**
     * @param array $data
     * @param string $key
     * @return string|null
     */
    protected function parseField(array $data, string $key): ?string
    {
        // full match
        if (isset($data[$key])) {
            return $key;
        }

        if (mb_strlen($key) > 100) {
            return null;
        }

        // json path
        $key = explode('->', $key);
        while (count($key) > 1) {
            array_pop($key);
            $pattern = implode('->', $key) . '->*';

            if (isset($data[$pattern])) {
                return $pattern;
            }
        }

        // nothing was found
        return null;
    }
}
