<?php

declare(strict_types=1);

namespace HpWebDeveloper\LaravelEnvSettings;

use HpWebDeveloper\LaravelEnvSettings\Contracts\EnvironmentAware;
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
}
