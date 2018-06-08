<?php

declare(strict_types=1);

namespace Fisher\SSO\Providers;

use App\Support\PackageHandler;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Boorstrap the service provider.
     *
     * @return void
     */
    public function boot()
    {
        // Register a database migration path.
        $this->loadMigrationsFrom($this->app->make('path.package-sso.migrations'));

        // Register translations.
        $this->loadTranslationsFrom($this->app->make('path.package-sso.lang'), 'package-sso');

        // Register view namespace.
        $this->loadViewsFrom($this->app->make('path.package-sso.views'), 'package-sso');

        // Publish public resource.
        $this->publishes([
            $this->app->make('path.package-sso.assets') => $this->app->publicPath().'/assets/package-sso',
        ], 'package-sso-public');

        // Publish config.
        $this->publishes([
            $this->app->make('path.package-sso.config').'/package-sso.php' => $this->app->configPath('package-sso.php'),
        ], 'package-sso-config');
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        // Bind all of the package paths in the container.
        $this->bindPathsInContainer();

        // Merge config.
        $this->mergeConfigFrom(
            $this->app->make('path.package-sso.config').'/package-sso.php',
            'package-sso'
        );

        // register cntainer aliases
        $this->registerCoreContainerAliases();

        // Register singletons.
        $this->registerSingletions();

        // Register package handlers.
        $this->registerPackageHandlers();
    }

    /**
     * Bind paths in container.
     *
     * @return void
     */
    protected function bindPathsInContainer()
    {
        foreach ([
            'path.package-sso' => $root = dirname(dirname(__DIR__)),
            'path.package-sso.assets' => $root.'/assets',
            'path.package-sso.config' => $root.'/config',
            'path.package-sso.database' => $database = $root.'/database',
            'path.package-sso.resources' => $resources = $root.'/resources',
            'path.package-sso.lang' => $resources.'/lang',
            'path.package-sso.views' => $resources.'/views',
            'path.package-sso.migrations' => $database.'/migrations',
            'path.package-sso.seeds' => $database.'/seeds',
        ] as $abstract => $instance) {
            $this->app->instance($abstract, $instance);
        }
    }

    /**
     * Register singletons.
     *
     * @return void
     */
    protected function registerSingletions()
    {
        // Owner handler.
        $this->app->singleton('package-sso:handler', function () {
            return new \Fisher\SSO\Handlers\PackageHandler();
        });

        // Develop handler.
        $this->app->singleton('package-sso:dev-handler', function ($app) {
            return new \Fisher\SSO\Handlers\DevPackageHandler($app);
        });
    }

    /**
     * Register the package class aliases in the container.
     *
     * @return void
     */
    protected function registerCoreContainerAliases()
    {
        foreach ([
            'package-sso:handler' => [
                \Fisher\SSO\Handlers\PackageHandler::class,
            ],
            'package-sso:dev-handler' => [
                \Fisher\SSO\Handlers\DevPackageHandler::class,
            ],
        ] as $abstract => $aliases) {
            foreach ($aliases as $alias) {
                $this->app->alias($abstract, $alias);
            }
        }
    }

    /**
     * Register package handlers.
     *
     * @return void
     */
    protected function registerPackageHandlers()
    {
        $this->loadHandleFrom('package-sso', 'package-sso:handler');
        $this->loadHandleFrom('package-sso-dev', 'package-sso:dev-handler');
    }

    /**
     * Register handler.
     *
     * @param string $name
     * @param \App\Support\PackageHandler|string $handler
     * @return void
     */
    private function loadHandleFrom(string $name, $handler)
    {
        PackageHandler::loadHandleFrom($name, $handler);
    }
}
