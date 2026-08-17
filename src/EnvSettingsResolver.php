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
        $overridePath = $this->resolveOverridePath();

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

    /**
     * Resolve the directory that override classes are loaded from.
     *
     * The configured value is interpreted at runtime — after the application has
     * booted — so no path helper is ever called at config-load time. This keeps
     * `php artisan config:cache` portable: nothing app-relative is frozen into
     * `bootstrap/cache/config.php`, so a cache built in CI or a Docker build
     * stage stays correct when the app runs from a different absolute path.
     *
     *   null                    → app_path('Settings/Overrides')
     *   'Custom/Overrides'      → app_path('Custom/Overrides')
     *   '/mnt/shared/overrides' → used as-is
     */
    private function resolveOverridePath(): ?string
    {
        $configured = config('env-settings.override_path');

        if ($configured === null || $configured === '') {
            return app_path('Settings/Overrides');
        }

        if (! is_string($configured)) {
            return null;
        }

        return $this->isAbsolutePath($configured)
            ? $configured
            : app_path($configured);
    }

    /**
     * Determine whether a path is absolute on the current platform.
     *
     * Covers POSIX roots (`/srv/...`), Windows drive roots (`C:\...`, `C:/...`)
     * and UNC shares (`\\server\share`).
     */
    private function isAbsolutePath(string $path): bool
    {
        return str_starts_with($path, '/')
            || str_starts_with($path, '\\')
            || preg_match('/^[A-Za-z]:[\\\\\/]/', $path) === 1;
    }
}
