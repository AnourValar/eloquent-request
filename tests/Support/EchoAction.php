<?php

namespace AnourValar\EloquentRequest\Tests\Support;

use AnourValar\EloquentRequest\Actions\ActionInterface;
use Illuminate\Database\Eloquent\Builder;

class EchoAction implements ActionInterface
{
    /**
     * @var string
     */
    public const OPTION_APPLY = 'action.echo.apply';

    /**
     * Makes the instance "callable" (required by Service::extendActions signature)
     *
     * @return void
     */
    public function __invoke(): void
    {
        //
    }

    /**
     * {@inheritDoc}
     */
    public function passes(array $profile, array $request, array $config): bool
    {
        return in_array(self::OPTION_APPLY, $profile['options']);
    }

    /**
     * {@inheritDoc}
     */
    public function validate(array $profile, array $request, array $config, \Closure $fail): void
    {
        //
    }

    /**
     * {@inheritDoc}
     */
    public function requestParameters(array $profile, array $request, array $config): array
    {
        return ['echo' => true];
    }

    /**
     * {@inheritDoc}
     */
    public function action(Builder &$query, array $profile, array $requestParameters, array $config, \Closure $fail)
    {
        return 'echo-action-result';
    }
}
