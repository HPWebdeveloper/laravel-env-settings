<?php

declare(strict_types=1);

namespace HpWebDeveloper\LaravelEnvSettings\Tests\Fixtures;

use HpWebDeveloper\LaravelEnvSettings\EnvironmentSettings;

final class FakeEnumSettings extends EnvironmentSettings
{
    /**
     * @param  list<PaymentMode>  $accepted
     */
    public function __construct(
        public PaymentMode $mode,
        public Tier $tier,
        public array $accepted,
        public string $currency,
    ) {}

    public static function development(): static
    {
        return new self(PaymentMode::Sandbox, Tier::Low, [PaymentMode::Sandbox], 'EUR');
    }

    public static function production(): static
    {
        return new self(PaymentMode::Live, Tier::High, [PaymentMode::Live, PaymentMode::Sandbox], 'EUR');
    }
}
