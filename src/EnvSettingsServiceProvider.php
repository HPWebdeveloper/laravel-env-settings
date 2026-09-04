<?php

declare(strict_types=1);

namespace HpWebDeveloper\LaravelEnvSettings;

use HpWebDeveloper\LaravelEnvSettings\Commands\CheckEnvSettingsCommand;
use HpWebDeveloper\LaravelEnvSettings\Commands\DiffEnvSettingsCommand;
use HpWebDeveloper\LaravelEnvSettings\Commands\MakeEnvSettingsCommand;
use HpWebDeveloper\LaravelEnvSettings\Commands\ShowEnvSettingsCommand;
use Illuminate\Support\ServiceProvider;

class EnvSettingsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/env-settings.php', 'env-settings');

        // Register the resolver as a singleton so it can be injected and mocked
        // independently of any individual settings class.
        $this->app->singleton(EnvSettingsResolver::class);

        $this->registerSettingsClasses();
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/env-settings.php' => config_path('env-settings.php'),
            ], 'env-settings-config');

            $this->commands([
                CheckEnvSettingsCommand::class,
                MakeEnvSettingsCommand::class,
                ShowEnvSettingsCommand::class,
                DiffEnvSettingsCommand::class,
            ]);
        }
    }

    protected function registerSettingsClasses(): void
    {
        $classes = config('env-settings.register', []);

        foreach ($classes as $class) {
            if (is_string($class) && is_subclass_of($class, EnvironmentSettings::class)) {
                $this->app->singleton(
                    $class,
                    fn ($app) => $app[EnvSettingsResolver::class]->resolve($class),
                );
            }
        }
    }
}
