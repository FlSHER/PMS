<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\SSO\CurlService;
use Illuminate\Support\Facades\Auth;
use App\Services\SSO\OAGuard;
use App\Services\SSO\OAUserProvider;

class SSOServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('curl', CurlService::class);

        Auth::provider('oa', function () {
            return new OAUserProvider();
        });

        Auth::extend('oa', function ($app, $name, array $config) {
            return new OAGuard(Auth::createUserProvider($config['provider']), $app->make('request'));
        });
    }
}
