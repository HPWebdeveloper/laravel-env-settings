<?php

declare(strict_types=1);

namespace HpWebDeveloper\LaravelEnvSettings\Tests\Fixtures;

use DateTimeImmutable;
use HpWebDeveloper\LaravelEnvSettings\EnvironmentSettings;

/**
 * Covers the two value shapes `env-settings:show` renders but `env-settings:diff`
 * did not: an object with no `__toString`, and a null beside an empty string.
 */
final class FakeObjectValuedSettings extends EnvironmentSettings
{
    public function __construct(
        public DateTimeImmutable $starts_at,
        public ?string $label,
    ) {}

    public static function development(): static
    {
        return new self(new DateTimeImmutable('2026-01-01 00:00:00'), null);
    }

    public static function production(): static
    {
        return new self(new DateTimeImmutable('2026-06-01 00:00:00'), '');
    }
}
