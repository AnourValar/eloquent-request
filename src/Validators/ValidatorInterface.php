<?php

namespace AnourValar\EloquentRequest\Validators;

interface ValidatorInterface
{
    /**
     * Collect an error
     *
     * @param mixed $key
     * @param string $message
     * @return \AnourValar\EloquentRequest\Validators\ValidatorInterface
     */
    public function addError($key, string $message): ValidatorInterface;

    /**
     * Make validation
     *
     * @param array $profile
     * @param array $config
     * @throws \Exception
     * @return void
     */
    public function validate(array $profile, array $config): void;
}
