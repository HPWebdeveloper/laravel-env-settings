<?php

declare(strict_types=1);

namespace HpWebDeveloper\LaravelEnvSettings;

use BackedEnum;
use HpWebDeveloper\LaravelEnvSettings\Contracts\EnvironmentAware;
use ReflectionClass;
use ReflectionProperty;
use UnitEnum;

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

            $data[$property->getName()] = $this->normalise($property->getValue($this));
        }

        return $data;
    }

    /**
     * Reduce a property value to something JSON can represent.
     *
     * Enums are unwrapped here rather than left to `json_encode`: a backed enum
     * would survive, but a pure one has no default serialisation, so a single
     * such property turns the whole encoded payload into `false` with no
     * exception to point at the cause.
     */
    private function normalise(mixed $value): mixed
    {
        if ($value instanceof self) {
            return $value->toArray();
        }

        if ($value instanceof BackedEnum) {
            return $value->value;
        }

        if ($value instanceof UnitEnum) {
            return $value->name;
        }

        if (is_array($value)) {
            return array_map(fn (mixed $item): mixed => $this->normalise($item), $value);
        }

        return $value;
    }
}
