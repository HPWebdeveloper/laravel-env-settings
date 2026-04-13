<?php

declare(strict_types=1);

if (! function_exists('envSettings')) {
    /**
     * Resolve an environment settings class from the container.
     *
     * @template T of \HpWebDeveloper\LaravelEnvSettings\EnvironmentSettings
     *
     * @param  class-string<T>  $class
     * @return T
     */
    function envSettings(string $class): mixed
    {
        return app($class);
    }
}
