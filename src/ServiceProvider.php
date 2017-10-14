<?php

namespace Frnkly\LaravelKeen;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('Frnkly\LaravelKeen\Client', function($app) {
            return new Client([
                'masterKey' => config('services.keen.master'),
                'writeKey'  => config('services.keen.write'),
                'projectId' => config('services.keen.id'),
            ]);
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['Frnkly\LaravelKeen\Client'];
    }
}
