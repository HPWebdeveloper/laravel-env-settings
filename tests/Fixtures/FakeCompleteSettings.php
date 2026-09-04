<?php

declare(strict_types=1);

namespace HpWebDeveloper\LaravelEnvSettings\Tests\Fixtures;

use HpWebDeveloper\LaravelEnvSettings\EnvironmentSettings;

final class FakeCompleteSettings extends EnvironmentSettings
{
    public function __construct(
        public string $domain,
        public int $retry_attempts,
    ) {}

    public static function development(): static
    {
        return new self('dev.example.com', 0);
    }

    public static function production(): static
    {
        return new self('example.com', 0);
    }
}
