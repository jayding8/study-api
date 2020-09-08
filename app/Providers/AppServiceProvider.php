<?php

namespace App\Providers;

use App\Contracts\SessionContract;
use App\Services\Auth\SessionService;
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
        // 获取第三方可转债数据
        $this->app->bind(KzzContract::class,KzzService::class);
        // 用户信息存储
        $this->app->bind(SessionContract::class,SessionService::class);
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
