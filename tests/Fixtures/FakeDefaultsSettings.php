<?php

declare(strict_types=1);

namespace HpWebDeveloper\LaravelEnvSettings\Tests\Fixtures;

use HpWebDeveloper\LaravelEnvSettings\EnvironmentSettings;

/**
 * Values that never vary, mostly written once as defaults.
 *
 * `currency`, `regions` and `timezone` hold their declared defaults in every
 * environment. `locale` has a default too, but every factory overrides it with
 * the same value, so it is still written out once per factory.
 */
final class FakeDefaultsSettings extends EnvironmentSettings
{
    // A default on a plain property is written once just the same.
    public string $timezone = 'UTC';

    /**
     * @param  list<string>  $regions
     */
    public function __construct(
        public string $provider,
        public float $temperature = 0.2,
        public string $currency = 'EUR',
        public array $regions = ['eu'],
        public string $locale = 'en',
    ) {}

    public static function development(): static
    {
        return new self(provider: 'ollama', locale: 'de');
    }

    public static function production(): static
    {
        return new self(provider: 'openai', locale: 'de');
    }

    public static function staging(): static
    {
        return new self(provider: 'openai', temperature: 0.9, locale: 'de');
    }
}
