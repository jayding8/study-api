<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Contracts\KzzContract;
use App\Services\Kzz\KzzService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(KzzContract::class,KzzService::class);
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
