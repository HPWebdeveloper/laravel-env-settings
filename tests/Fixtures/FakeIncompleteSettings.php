<?php

declare(strict_types=1);

namespace HpWebDeveloper\LaravelEnvSettings\Tests\Fixtures;

use HpWebDeveloper\LaravelEnvSettings\Attributes\AllowEmpty;
use HpWebDeveloper\LaravelEnvSettings\EnvironmentSettings;

/**
 * production() was never filled in: development() has real values, so the
 * placeholders left behind are the signature of a forgotten factory.
 */
final class FakeIncompleteSettings extends EnvironmentSettings
{
    public function __construct(
        public string $domain,
        public int $timeout,
        // Zero in every environment — deliberate, so never reported.
        public int $retry_attempts,
        // Set in development, empty in production on purpose: only the
        // attribute stops this being reported.
        #[AllowEmpty] public string $path_prefix,
        // A placeholder the developer typed into the value itself.
        public string $webhook_url,
    ) {}

    public static function development(): static
    {
        return new self('dev.example.com', 30, 0, '/api', 'https://dev.example.com/hook');
    }

    public static function production(): static
    {
        return new self('', 0, 0, '', 'TODO: set this');
    }
}
