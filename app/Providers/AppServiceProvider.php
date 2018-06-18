<?php

namespace App\Providers;

use App\Services\Admin\EventService;
use App\Services\Admin\EventTypeService;
use App\Services\SSO\OAUser;
use App\Services\SSO\OAUserProvider;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
//        $this->app->singleton('eventType', EventTypeService::class);
//        $this->app->singleton('event',EventService::class);
        $this->app->singleton('OAUser',OAUserProvider::class);
    }
}
