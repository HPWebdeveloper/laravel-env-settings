<?php

declare(strict_types=1);
use HpWebDeveloper\LaravelEnvSettings\EnvironmentSettings;

if (! function_exists('envSettings')) {
    /**
     * Resolve an environment settings class from the container.
     *
     * Falls back to the class's own ::resolve() factory when it has not been
     * registered in config('env-settings.register'), so callers can use the
     * helper regardless of registration state.
     *
     * @template T of \HpWebDeveloper\LaravelEnvSettings\EnvironmentSettings
     *
     * @param  class-string<T>  $class
     * @return T
     */
    function envSettings(string $class): EnvironmentSettings
    {
        if (app()->bound($class)) {
            return app($class);
        }

        if (is_subclass_of($class, EnvironmentSettings::class)) {
            return $class::resolve();
        }

        throw new InvalidArgumentException(
            "Class [{$class}] must extend ".EnvironmentSettings::class.'.'
        );
    }
}
