<?php

declare(strict_types=1);

namespace HpWebDeveloper\LaravelEnvSettings\Tests\Feature;

use HpWebDeveloper\LaravelEnvSettings\EnvSettingsServiceProvider;
use HpWebDeveloper\LaravelEnvSettings\Tests\Fixtures\FakeAuthSettings;
use HpWebDeveloper\LaravelEnvSettings\Tests\TestCase;

class ServiceProviderTest extends TestCase
{
    public function test_config_is_merged(): void
    {
        $this->assertIsArray(config('env-settings'));
        $this->assertArrayHasKey('environment_map', config('env-settings'));
        $this->assertArrayHasKey('fallback_environment', config('env-settings'));
        $this->assertArrayHasKey('override', config('env-settings'));
        $this->assertArrayHasKey('register', config('env-settings'));
    }

    public function test_auto_registered_settings_are_singletons(): void
    {
        // Reconfigure with a registered class
        config()->set('env-settings.register', [FakeAuthSettings::class]);

        // Re-register so the provider picks up the new config
        $this->app->register(EnvSettingsServiceProvider::class, true);

        $first = app(FakeAuthSettings::class);
        $second = app(FakeAuthSettings::class);

        $this->assertSame($first, $second);
    }
}
