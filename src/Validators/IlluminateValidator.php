<?php

namespace AnourValar\EloquentRequest\Validators;

class IlluminateValidator implements ValidatorInterface
{
    /**
     * @var array
     */
    protected $errors;

   /**
    * {@inheritDoc}
    * @see \AnourValar\EloquentRequest\Validators\ValidatorInterface::addError()
    */
   public function addError($key, string $message): ValidatorInterface
    {
        $this->errors[] = ['key' => $key, 'message' => $message];

        return $this;
    }

    /**
     * {@inheritDoc}
     * @see \AnourValar\EloquentRequest\Validators\ValidatorInterface::validate()
     */
    public function validate(array $profile, array $config): void
    {
        if (! $this->errors) {
            return;
        }

        \Validator
            ::make([], [])
            ->after(function ($validator) use ($config)
            {
                foreach ($this->errors as $error) {
                    $validator->errors()->add($this->prepareKey($error['key'], $config), $error['message']);
                }
            })
            ->validate();
    }

    /**
     * @param mixed $key
     * @param array $config
     * @return string
     */
    protected function prepareKey($key, array $config): string
    {
        return implode($config['validator_key_delimiter'], array_diff((array) $key, [null]));
    }
}
