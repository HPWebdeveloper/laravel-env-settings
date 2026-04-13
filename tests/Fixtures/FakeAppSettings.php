<?php

declare(strict_types=1);

namespace HpWebDeveloper\LaravelEnvSettings\Tests\Fixtures;

use HpWebDeveloper\LaravelEnvSettings\EnvironmentSettings;

class FakeAppSettings extends EnvironmentSettings
{
    public function __construct(
        public FakeAuthSettings $auth,
        public FakePaymentSettings $payment,
    ) {}

    public static function development(): static
    {
        return new static(
            auth: FakeAuthSettings::development(),
            payment: FakePaymentSettings::development(),
        );
    }

    public static function production(): static
    {
        return new static(
            auth: FakeAuthSettings::production(),
            payment: FakePaymentSettings::production(),
        );
    }

    public static function staging(): static
    {
        return new static(
            auth: FakeAuthSettings::staging(),
            payment: FakePaymentSettings::staging(),
        );
    }
}
