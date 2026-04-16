<?php

declare(strict_types=1);

namespace HpWebDeveloper\LaravelEnvSettings;

use HpWebDeveloper\LaravelEnvSettings\Contracts\EnvironmentAware;
use Illuminate\Support\Facades\File;
use Spatie\LaravelData\Data;

abstract class EnvironmentSettings extends Data implements EnvironmentAware
{
    abstract public static function development(): static;

    abstract public static function production(): static;

    public static function staging(): static
    {
        return static::development();
    }

    public static function testing(): static
    {
        return static::development();
    }

    public static function resolve(): static
    {
        if (static::shouldUseOverride()) {
            $overrideClass = static::resolveOverrideClass();

            if ($overrideClass !== null) {
                return $overrideClass::resolve();
            }
        }

        return static::resolveForEnvironment();
    }

    protected static function resolveForEnvironment(): static
    {
        $appEnv = app()->environment();

        $map = config('env-settings.environment_map', []);
        $mapped = $map[$appEnv] ?? $appEnv;

        if (method_exists(static::class, $mapped)) {
            return static::{$mapped}();
        }

        $fallback = config('env-settings.fallback_environment', 'development');

        if (method_exists(static::class, $fallback)) {
            return static::{$fallback}();
        }

        return static::development();
    }

    protected static function shouldUseOverride(): bool
    {
        return (bool) config('env-settings.override', false);
    }

    /**
     * Look for an override class matching the current settings class name
     * in the configured override path.
     *
     * @return class-string<static>|null
     */
    protected static function resolveOverrideClass(): ?string
    {
        $overridePath = config('env-settings.override_path');

        if (! $overridePath || ! is_dir($overridePath)) {
            return null;
        }

        $shortName = class_basename(static::class);
        $filePath = rtrim($overridePath, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$shortName.'.php';

        if (! File::exists($filePath)) {
            return null;
        }

        // Derive the override class FQCN from the configured namespace
        $overrideNamespace = config('env-settings.override_namespace');

        if (! $overrideNamespace) {
            return null;
        }

        $overrideClass = rtrim($overrideNamespace, '\\').'\\'.$shortName;

        if (! class_exists($overrideClass) || ! is_subclass_of($overrideClass, static::class)) {
            return null;
        }

        return $overrideClass;
    }
}
