<?php

declare(strict_types=1);

namespace Fisher\SSO\Providers;

use Fisher\SSO\Services\OAGuard;
use Fisher\SSO\Services\OAUserProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;
use Fisher\SSO\Services\RequestSSOService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Boorstrap the service provider.
     *
     * @return void
     */
    public function boot()
    {
        // dd($this->app->make('path.sso.lang'));
        // Register translations.
        $this->loadTranslationsFrom($this->app->make('path.sso.lang'), 'sso');

        // Publish config.
        $this->publishes([$this->app->make('path.sso.config') . '/sso.php' => $this->app->configPath('sso.php')]);
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->bindPathsInContainer();

        $this->registerSSOService();

        $this->registerSingletions();
    }

    /**
     * register sso service.
     *
     * @return void
     */
    protected function registerSSOService()
    {
        Auth::provider('oa', function () {
            return new OAUserProvider();
        });

        Auth::extend('oa', function ($app, $name, array $config) {
            return new OAGuard(Auth::createUserProvider($config['provider']), $app->make('request'));
        });
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function registerSingletions()
    {
        $this->app->singleton('api', function ($app) {
            return new RequestSSOService($app->make('request'));
        });
    }

    /**
     * Bind paths in container.
     *
     * @return void
     */
    protected function bindPathsInContainer()
    {
        foreach ([
            'path.sso' => $root = dirname(dirname(__DIR__)),
            'path.sso.config' => $root . '/config',
            'path.sso.resources' => $resources = $root . '/resources',
            'path.sso.lang' => $resources . '/lang',
        ] as $abstract => $instance) {
            $this->app->instance($abstract, $instance);
        }
    }
}
