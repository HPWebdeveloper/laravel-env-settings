<?php

declare(strict_types=1);

namespace HpWebDeveloper\LaravelEnvSettings\Contracts;

interface EnvironmentAware
{
    public static function development(): static;

    public static function production(): static;

    public static function resolve(): static;
}
