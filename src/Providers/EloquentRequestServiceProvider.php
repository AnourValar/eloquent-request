<?php

namespace AnourValar\EloquentRequest\Providers;

use Illuminate\Support\ServiceProvider;

class EloquentRequestServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // config
        $this->mergeConfigFrom(__DIR__.'/../resources/config/eloquent_request.php', 'eloquent_request');

        $this->app->singleton(\AnourValar\EloquentRequest\Service::class, function ($app)
        {
            return new \AnourValar\EloquentRequest\Service;
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // config
        $this->publishes([ __DIR__.'/../resources/config/eloquent_request.php' => config_path('eloquent_request.php')], 'config');

        // langs
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang/', 'eloquent-request');
        $this->publishes([__DIR__.'/../resources/lang/' => lang_path('vendor/eloquent-request')]);

        // commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                \AnourValar\EloquentRequest\Console\Commands\ControllerMakeCommand::class,
            ]);
        }
    }
}
