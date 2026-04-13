<?php

declare(strict_types=1);

namespace HpWebDeveloper\LaravelEnvSettings;

use Illuminate\Support\ServiceProvider;

class EnvSettingsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/env-settings.php', 'env-settings');

        $this->registerSettingsClasses();
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/env-settings.php' => config_path('env-settings.php'),
            ], 'env-settings-config');
        }
    }

    protected function registerSettingsClasses(): void
    {
        $classes = config('env-settings.register', []);

        foreach ($classes as $class) {
            if (is_string($class) && is_subclass_of($class, EnvironmentSettings::class)) {
                $this->app->singleton($class, fn () => $class::resolve());
            }
        }
    }
}
