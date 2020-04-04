<?php

namespace AnourValar\EloquentRequest\Helpers;

class Validator
{
    /**
     * @var array
     */
    protected $errors;

    /**
     * @var array
     */
    protected $config;

    /**
     * @param array $config
     * @return \AnourValar\EloquentRequest\Helpers\Validator
     */
    public function setConfig(array $config) : self
    {
        $this->config = $config;

        return $this;
    }

    /**
     * @param mixed $key
     * @param string $message
     * @return \AnourValar\EloquentRequest\Helpers\Validator
     */
    public function addError($key, string $message) : self
    {
        $this->errors[] = [
            'key' => implode($this->config['validator_key_delimiter'], array_diff((array)$key, [null])),
            'message' => $message,
        ];

        return $this;
    }

    /**
     * @throws \Illuminate\Validation\ValidationException
     * @return void
     */
    public function validate() : void
    {
        if (! $this->errors) {
            return;
        }

        \Validator
            ::make([], [])
            ->after(function ($validator)
            {
                foreach ($this->errors as $error) {
                    $validator->errors()->add($error['key'], $error['message']);
                }
            })
            ->validate();
    }
}
