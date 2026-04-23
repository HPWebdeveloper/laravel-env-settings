<?php

declare(strict_types=1);

namespace HpWebDeveloper\LaravelEnvSettings;

use Illuminate\Support\Facades\File;

class EnvSettingsResolver
{
    /**
     * Resolve the correct settings instance for the current environment.
     *
     * Resolution order:
     *   1. If overrides are enabled and an override class exists, delegate to it.
     *   2. Map the current APP_ENV through `env-settings.environment_map`.
     *   3. Call the matching static method on the class.
     *   4. Fall back to `fallback_environment`, then `development()`.
     *
     * Uses `app()->environment()` and `config()` throughout — never `env()` —
     * so it is fully compatible with `php artisan config:cache`.
     *
     * @template T of EnvironmentSettings
     *
     * @param  class-string<T>  $class
     * @return T
     */
    public function resolve(string $class): EnvironmentSettings
    {
        if ($this->shouldUseOverride()) {
            $overrideClass = $this->resolveOverrideClass($class);

            if ($overrideClass !== null) {
                return $this->resolve($overrideClass);
            }
        }

        return $this->resolveForEnvironment($class);
    }

    /**
     * @template T of EnvironmentSettings
     *
     * @param  class-string<T>  $class
     * @return T
     */
    private function resolveForEnvironment(string $class): EnvironmentSettings
    {
        $appEnv = app()->environment();

        $map = config('env-settings.environment_map', []);
        $mapped = $map[$appEnv] ?? $appEnv;

        if (method_exists($class, $mapped)) {
            return $class::{$mapped}();
        }

        $fallback = config('env-settings.fallback_environment', 'development');

        if (method_exists($class, $fallback)) {
            return $class::{$fallback}();
        }

        return $class::development();
    }

    private function shouldUseOverride(): bool
    {
        return (bool) config('env-settings.override', false);
    }

    /**
     * Look for an override class for the given settings class.
     *
     * Requires explicit `override_path` and `override_namespace` configuration —
     * no filesystem scanning or namespace derivation from reflection.
     *
     * @param  class-string<EnvironmentSettings>  $class
     * @return class-string<EnvironmentSettings>|null
     */
    private function resolveOverrideClass(string $class): ?string
    {
        // override_path defaults to null in the config; fall back to app_path() at
        // runtime so the value is resolved after the application is fully booted,
        // keeping config:cache safe (no app_path() call at config-load time).
        $overridePath = config('env-settings.override_path') ?? app_path('Settings/Overrides');

        if (! $overridePath || ! is_dir($overridePath)) {
            return null;
        }

        $shortName = class_basename($class);
        $filePath = rtrim($overridePath, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$shortName.'.php';

        if (! File::exists($filePath)) {
            return null;
        }

        $overrideNamespace = config('env-settings.override_namespace');

        if (! $overrideNamespace) {
            return null;
        }

        $overrideClass = rtrim($overrideNamespace, '\\').'\\'.$shortName;

        if (! class_exists($overrideClass) || ! is_subclass_of($overrideClass, $class)) {
            return null;
        }

        return $overrideClass;
    }
}
