<?php

declare(strict_types=1);

namespace HpWebDeveloper\LaravelEnvSettings\Tests\Unit;

use HpWebDeveloper\LaravelEnvSettings\Tests\Fixtures\FakeAuthSettings;
use HpWebDeveloper\LaravelEnvSettings\Tests\Fixtures\FakePaymentSettings;
use HpWebDeveloper\LaravelEnvSettings\Tests\TestCase;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionProperty;

class EnvironmentSettingsTest extends TestCase
{
    public function test_resolve_returns_production_when_env_is_production(): void
    {
        app()->detectEnvironment(fn () => 'production');

        $settings = FakeAuthSettings::resolve();

        $this->assertSame('auth.example.com', $settings->domain);
        $this->assertSame(10, $settings->timeout);
        $this->assertTrue($settings->mfa_enabled);
    }

    public function test_resolve_returns_development_when_env_is_local(): void
    {
        app()->detectEnvironment(fn () => 'local');

        $settings = FakeAuthSettings::resolve();

        $this->assertSame('dev.auth.example.com', $settings->domain);
        $this->assertSame(30, $settings->timeout);
        $this->assertFalse($settings->mfa_enabled);
    }

    public function test_resolve_returns_staging_when_env_is_staging(): void
    {
        app()->detectEnvironment(fn () => 'staging');

        $settings = FakeAuthSettings::resolve();

        $this->assertSame('staging.auth.example.com', $settings->domain);
        $this->assertSame(15, $settings->timeout);
    }

    public function test_resolve_maps_dev_to_development(): void
    {
        app()->detectEnvironment(fn () => 'dev');

        $settings = FakeAuthSettings::resolve();

        $this->assertSame('dev.auth.example.com', $settings->domain);
    }

    public function test_resolve_falls_back_to_development_for_unknown_env(): void
    {
        app()->detectEnvironment(fn () => 'some-unknown-env');

        $settings = FakeAuthSettings::resolve();

        $this->assertSame('dev.auth.example.com', $settings->domain);
    }

    public function test_staging_defaults_to_development_when_not_overridden(): void
    {
        app()->detectEnvironment(fn () => 'staging');

        // FakePaymentSettings does NOT override staging(), so it should fall back to development()
        $settings = FakePaymentSettings::resolve();

        $this->assertSame('test', $settings->mode);
        $this->assertSame(1, $settings->retry_attempts);
    }

    public function test_testing_defaults_to_development_when_not_overridden(): void
    {
        app()->detectEnvironment(fn () => 'testing');

        $settings = FakePaymentSettings::resolve();

        $this->assertSame('test', $settings->mode);
    }

    public function test_resolve_respects_custom_environment_map(): void
    {
        config()->set('env-settings.environment_map.my-custom-env', 'production');
        app()->detectEnvironment(fn () => 'my-custom-env');

        $settings = FakeAuthSettings::resolve();

        $this->assertSame('auth.example.com', $settings->domain);
    }

    public function test_resolve_respects_fallback_environment_config(): void
    {
        config()->set('env-settings.fallback_environment', 'production');
        app()->detectEnvironment(fn () => 'nonexistent-env');

        $settings = FakeAuthSettings::resolve();

        $this->assertSame('auth.example.com', $settings->domain);
    }

    public function test_settings_properties_are_typed(): void
    {
        // Asserting the runtime type of a typed property only re-checks what
        // PHP already guarantees. Read the declarations instead, which is
        // what the promoted constructor is actually responsible for.
        $declared = [];

        foreach ((new ReflectionClass(FakeAuthSettings::class))->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            $type = $property->getType();

            $declared[$property->getName()] = $type instanceof ReflectionNamedType
                ? $type->getName()
                : null;
        }

        $this->assertSame([
            'domain' => 'string',
            'redirect_url' => 'string',
            'timeout' => 'int',
            'mfa_enabled' => 'bool',
        ], $declared);
    }
}
