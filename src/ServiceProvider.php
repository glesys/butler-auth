<?php

namespace Butler\Auth;

use Illuminate\Auth\RequestGuard;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class ServiceProvider extends BaseServiceProvider
{
    public function register()
    {
        config(['auth.guards.butler.driver' => 'butler']);
    }

    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../database/migrations' => database_path('migrations'),
            ], 'butler-auth-migrations');
        }

        $this->configureGuard();
    }

    protected function configureGuard()
    {
        Auth::resolved(function ($auth) {
            $requestGuardCreator = fn () => $this->createGuard($auth);

            $auth->extend('butler', function () use ($requestGuardCreator) {
                return tap($requestGuardCreator(), function ($guard) {
                    app()->refresh('request', $guard, 'setRequest');
                });
            });
        });
    }

    protected function createGuard($auth)
    {
        return new RequestGuard(
            new Guard(),
            $this->app['request'],
            $auth->createUserProvider()
        );
    }
}
