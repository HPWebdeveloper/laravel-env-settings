<?php

declare(strict_types=1);

namespace HpWebDeveloper\LaravelEnvSettings\Tests\Fixtures;

use HpWebDeveloper\LaravelEnvSettings\EnvironmentSettings;

final class FakePaymentSettings extends EnvironmentSettings
{
    public function __construct(
        public string $mode,
        public string $currency,
        public int $retry_attempts,
    ) {}

    public static function development(): static
    {
        return new self(
            mode: 'test',
            currency: 'EUR',
            retry_attempts: 1,
        );
    }

    public static function production(): static
    {
        return new static(
            mode: 'live',
            currency: 'EUR',
            retry_attempts: 5,
        );
    }
}
