<?php

namespace Butler\Auth;

use Butler\Auth\Commands\GenerateSecretKey;
use Butler\Auth\Commands\GenerateToken;
use Illuminate\Contracts\Container\Container;
use Illuminate\Foundation\Application as LaravelApplication;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use Laravel\Lumen\Application as LumenApplication;

class ServiceProvider extends BaseServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {

    }

    /**
     * Boot the service provider.
     *
     * @return void
     */
    public function boot()
    {
        $this->setupConfig($this->app);

        if ($this->app->runningInConsole()) {
            $this->commands([
                GenerateSecretKey::class,
                GenerateToken::class,
            ]);
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

    protected function setupConfig(Container $app)
    {
        $source = realpath($raw = __DIR__.'/../config/butler.php') ?: $raw;

        if ($app instanceof LaravelApplication && $app->runningInConsole()) {
            $this->publishes([$source => config_path('butler.php')]);
        } elseif ($app instanceof LumenApplication) {
            $app->configure('butler');
        }

        $this->mergeConfigFrom($source, 'butler');
    }
}

