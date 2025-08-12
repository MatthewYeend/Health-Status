<?php

namespace MattYeend\HealthStatus;

use Illuminate\Support\ServiceProvider;
use MattYeend\HealthStatus\Console\CronHeartbeat;

class HealthStatusServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/healthstatus.php', 'healthstatus');
        $this->app->singleton(HealthChecker::class, fn($app) => new HealthChecker(config('healthstatus')));
    }

    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/healthstatus.php' => config_path('healthstatus.php'),
        ], 'config');

        $this->publishes([
            __DIR__.'/../resources/views/status.blade.php' => resource_path('views/vendor/healthstatus/status.blade.php'),
        ], 'views');

        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'healthstatus');

        if ($this->app->runningInConsole()) {
            $this->commands([
                CronHeartbeat::class,
            ]);
        }
    }
}
