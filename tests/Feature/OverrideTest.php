<?php

declare(strict_types=1);

namespace HpWebDeveloper\LaravelEnvSettings\Tests\Feature;

use HpWebDeveloper\LaravelEnvSettings\Tests\Fixtures\FakeAuthSettings;
use HpWebDeveloper\LaravelEnvSettings\Tests\Fixtures\FakePaymentSettings;
use HpWebDeveloper\LaravelEnvSettings\Tests\TestCase;

class OverrideTest extends TestCase
{
    private function enableOverride(): void
    {
        config()->set('env-settings.override', true);
        config()->set('env-settings.override_path', __DIR__.'/../Fixtures/Overrides');
        config()->set('env-settings.override_namespace', 'HpWebDeveloper\\LaravelEnvSettings\\Tests\\Fixtures\\Overrides');
    }

    // -------------------------------------------------------------------
    // Override active
    // -------------------------------------------------------------------

    public function test_resolve_uses_override_class_when_enabled(): void
    {
        app()->detectEnvironment(fn () => 'local');
        $this->enableOverride();

        $settings = FakeAuthSettings::resolve();

        $this->assertSame('my-local-override.test', $settings->domain);
        $this->assertSame('http://my-local:8080/callback', $settings->redirect_url);
        $this->assertSame(60, $settings->timeout);
    }

    public function test_override_class_still_resolves_environment_correctly(): void
    {
        app()->detectEnvironment(fn () => 'production');
        $this->enableOverride();

        // The override class only overrides development().
        // For production, it inherits from the parent, so production values used.
        $settings = FakeAuthSettings::resolve();

        $this->assertSame('auth.example.com', $settings->domain);
        $this->assertSame(10, $settings->timeout);
    }

    // -------------------------------------------------------------------
    // Override disabled
    // -------------------------------------------------------------------

    public function test_resolve_ignores_override_when_disabled(): void
    {
        app()->detectEnvironment(fn () => 'local');
        config()->set('env-settings.override', false);
        config()->set('env-settings.override_path', __DIR__.'/../Fixtures/Overrides');
        config()->set('env-settings.override_namespace', 'HpWebDeveloper\\LaravelEnvSettings\\Tests\\Fixtures\\Overrides');

        $settings = FakeAuthSettings::resolve();

        // Should use original development() values, not override
        $this->assertSame('dev.auth.example.com', $settings->domain);
        $this->assertSame(30, $settings->timeout);
    }

    // -------------------------------------------------------------------
    // Override file doesn't exist for this class
    // -------------------------------------------------------------------

    public function test_resolve_falls_back_to_normal_when_no_override_file(): void
    {
        app()->detectEnvironment(fn () => 'local');
        $this->enableOverride();

        // FakePaymentSettings has no override file in Overrides/
        $settings = FakePaymentSettings::resolve();

        $this->assertSame('test', $settings->mode);
        $this->assertSame(1, $settings->retry_attempts);
    }

    // -------------------------------------------------------------------
    // Override path doesn't exist
    // -------------------------------------------------------------------

    public function test_resolve_falls_back_when_override_path_missing(): void
    {
        app()->detectEnvironment(fn () => 'local');
        config()->set('env-settings.override', true);
        config()->set('env-settings.override_path', __DIR__.'/../Fixtures/NonExistentFolder');
        config()->set('env-settings.override_namespace', 'HpWebDeveloper\\LaravelEnvSettings\\Tests\\Fixtures\\NonExistent');

        $settings = FakeAuthSettings::resolve();

        $this->assertSame('dev.auth.example.com', $settings->domain);
    }

    // -------------------------------------------------------------------
    // Override namespace not configured
    // -------------------------------------------------------------------

    public function test_resolve_falls_back_when_override_namespace_is_null(): void
    {
        app()->detectEnvironment(fn () => 'local');
        config()->set('env-settings.override', true);
        config()->set('env-settings.override_path', __DIR__.'/../Fixtures/Overrides');
        config()->set('env-settings.override_namespace', null);

        $settings = FakeAuthSettings::resolve();

        $this->assertSame('dev.auth.example.com', $settings->domain);
    }
}
