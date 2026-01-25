<?php

namespace Vendor\Package;

use Illuminate\Support\ServiceProvider;

class PackageServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/package.php', 'package');
    }

    /**
     * Bootstrap any package services.
     */
    public function boot(): void
    {
        // Publish config
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/package.php' => config_path('package.php'),
            ], 'package-config');

            $this->publishes([
                __DIR__.'/../database/migrations' => database_path('migrations'),
            ], 'package-migrations');

            $this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/package'),
            ], 'package-views');
        }

        // Load package resources
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'package');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        // If you add routes later, uncomment:
        // $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
    }
}
