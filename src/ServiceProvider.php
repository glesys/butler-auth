<?php

namespace Butler\Auth;

use Butler\Auth\Commands\GenerateToken;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class ServiceProvider extends BaseServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->configure('butler');
    }

    /**
     * Boot the service provider.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([GenerateToken::class]);
        }

        $this->app['auth']->extend('jwt', function ($app, $config) {
            return new JwtGuard(
                $app['request'],
                $app['config']['butler.auth.secret_key'],
                $app['config']['butler.auth.required_claims'],
                $app['auth']->createUserProvider($config['provider'] ?? null)
            );
        });
    }
}

