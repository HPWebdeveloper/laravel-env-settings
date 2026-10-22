<?php

declare(strict_types=1);

namespace HpWebDeveloper\LaravelEnvSettings\Tests\Fixtures;

use HpWebDeveloper\LaravelEnvSettings\EnvironmentSettings;

/**
 * A root class with one child that differs between environments and one that
 * does not.
 *
 * `payment` is built by a separate call in each factory, so the two values
 * are equal but never the same instance.
 */
final class FakeComposedSettings extends EnvironmentSettings
{
    public function __construct(
        public FakeAuthSettings $auth,
        public FakePaymentSettings $payment,
    ) {}

    public static function development(): static
    {
        return new self(FakeAuthSettings::development(), FakePaymentSettings::development());
    }

    public static function production(): static
    {
        return new self(FakeAuthSettings::production(), FakePaymentSettings::development());
    }
}
