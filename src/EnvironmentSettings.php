<?php

declare(strict_types=1);

namespace HpWebDeveloper\LaravelEnvSettings;

use HpWebDeveloper\LaravelEnvSettings\Contracts\EnvironmentAware;
use ReflectionClass;
use ReflectionProperty;

abstract class EnvironmentSettings implements EnvironmentAware
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

    /**
     * Resolve the correct instance for the current environment.
     *
     * Delegates to the container-bound {@see EnvSettingsResolver} so the
     * resolution strategy is injectable and testable without the class
     * knowing how to build itself.
     */
    public static function resolve(): static
    {
        /** @var static */
        return app(EnvSettingsResolver::class)->resolve(static::class);
    }

    /**
     * Return the public, typed properties of this settings instance as an array.
     *
     * Nested {@see EnvironmentSettings} instances are recursively expanded so the
     * result is a plain, JSON-serialisable structure.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $reflection = new ReflectionClass($this);
        $data = [];

        foreach ($reflection->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            if ($property->isStatic()) {
                continue;
            }

            $value = $property->getValue($this);

            $data[$property->getName()] = $value instanceof self
                ? $value->toArray()
                : $value;
        }

        return $data;
    }
}
