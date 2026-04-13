<?php

declare(strict_types=1);

namespace HpWebDeveloper\LaravelEnvSettings\Tests\Fixtures;

use HpWebDeveloper\LaravelEnvSettings\EnvironmentSettings;

class FakeAuthSettings extends EnvironmentSettings
{
    public function __construct(
        public string $domain,
        public string $redirect_url,
        public int $timeout,
        public bool $mfa_enabled,
    ) {}

    public static function development(): static
    {
        return new static(
            domain: 'dev.auth.example.com',
            redirect_url: 'http://localhost:8000/callback',
            timeout: 30,
            mfa_enabled: false,
        );
    }

    public static function production(): static
    {
        return new static(
            domain: 'auth.example.com',
            redirect_url: 'https://app.example.com/callback',
            timeout: 10,
            mfa_enabled: true,
        );
    }

    public static function staging(): static
    {
        return new static(
            domain: 'staging.auth.example.com',
            redirect_url: 'https://staging.example.com/callback',
            timeout: 15,
            mfa_enabled: true,
        );
    }
}
