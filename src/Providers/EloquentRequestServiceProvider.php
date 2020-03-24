<?php

namespace AnourValar\EloquentRequest\Providers;

use Illuminate\Support\ServiceProvider;

class EloquentRequestServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // langs
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang/', 'eloquent-request');
        $this->publishes([__DIR__.'/../resources/lang/' => resource_path('lang/vendor/eloquent-request')]);

        // commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                \AnourValar\EloquentRequest\Console\Commands\ControllerMakeCommand::class,
            ]);
        }
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(\AnourValar\EloquentRequest\Service::class, function ($app)
        {
            return new \AnourValar\EloquentRequest\Service;
        });
    }
}
