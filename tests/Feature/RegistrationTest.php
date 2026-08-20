<?php

declare(strict_types=1);

namespace HpWebDeveloper\LaravelEnvSettings\Tests\Feature;

use HpWebDeveloper\LaravelEnvSettings\EnvSettingsServiceProvider;
use HpWebDeveloper\LaravelEnvSettings\Tests\Fixtures\FakeAppSettings;
use HpWebDeveloper\LaravelEnvSettings\Tests\Fixtures\FakeAuthSettings;
use HpWebDeveloper\LaravelEnvSettings\Tests\Fixtures\FakePaymentSettings;
use HpWebDeveloper\LaravelEnvSettings\Tests\TestCase;

class RegistrationTest extends TestCase
{
    // -------------------------------------------------------------------
    // Auto-registration via config('env-settings.register')
    // -------------------------------------------------------------------

    public function test_auto_registered_class_resolves_for_current_environment(): void
    {
        app()->detectEnvironment(fn () => 'production');
        config()->set('env-settings.register', [FakeAuthSettings::class]);
        $this->application()->register(EnvSettingsServiceProvider::class, true);

        $settings = app(FakeAuthSettings::class);

        $this->assertSame('auth.example.com', $settings->domain);
        $this->assertTrue($settings->mfa_enabled);
    }

    public function test_auto_registered_class_resolves_development_for_local(): void
    {
        app()->detectEnvironment(fn () => 'local');
        config()->set('env-settings.register', [FakeAuthSettings::class]);
        $this->application()->register(EnvSettingsServiceProvider::class, true);

        $settings = app(FakeAuthSettings::class);

        $this->assertSame('dev.auth.example.com', $settings->domain);
        $this->assertFalse($settings->mfa_enabled);
    }

    public function test_multiple_classes_can_be_auto_registered(): void
    {
        app()->detectEnvironment(fn () => 'production');
        config()->set('env-settings.register', [
            FakeAuthSettings::class,
            FakePaymentSettings::class,
        ]);
        $this->application()->register(EnvSettingsServiceProvider::class, true);

        $auth = app(FakeAuthSettings::class);
        $payment = app(FakePaymentSettings::class);

        $this->assertSame('auth.example.com', $auth->domain);
        $this->assertSame('live', $payment->mode);
        $this->assertSame(5, $payment->retry_attempts);
    }

    public function test_auto_registered_classes_are_singletons(): void
    {
        config()->set('env-settings.register', [FakeAuthSettings::class]);
        $this->application()->register(EnvSettingsServiceProvider::class, true);

        $first = app(FakeAuthSettings::class);
        $second = app(FakeAuthSettings::class);

        $this->assertSame($first, $second);
    }

    public function test_invalid_class_in_register_array_is_ignored(): void
    {
        config()->set('env-settings.register', [
            'NonExistent\\ClassName',
            FakeAuthSettings::class,
        ]);
        $this->application()->register(EnvSettingsServiceProvider::class, true);

        // The valid class should still resolve
        $settings = app(FakeAuthSettings::class);
        $this->assertInstanceOf(FakeAuthSettings::class, $settings);
    }

    // -------------------------------------------------------------------
    // Manual singleton registration (user does it in AppServiceProvider)
    // -------------------------------------------------------------------

    public function test_manual_singleton_registration_works(): void
    {
        app()->detectEnvironment(fn () => 'production');

        $this->application()->singleton(FakeAuthSettings::class, fn () => FakeAuthSettings::resolve());

        $settings = app(FakeAuthSettings::class);

        $this->assertSame('auth.example.com', $settings->domain);
    }

    public function test_manual_registration_is_singleton(): void
    {
        $this->application()->singleton(FakePaymentSettings::class, fn () => FakePaymentSettings::resolve());

        $first = app(FakePaymentSettings::class);
        $second = app(FakePaymentSettings::class);

        $this->assertSame($first, $second);
    }

    // -------------------------------------------------------------------
    // Root composition pattern (nested settings)
    // -------------------------------------------------------------------

    public function test_root_settings_resolves_nested_sub_settings(): void
    {
        app()->detectEnvironment(fn () => 'production');

        $this->application()->singleton(FakeAppSettings::class, fn () => FakeAppSettings::resolve());

        $app = app(FakeAppSettings::class);

        $this->assertInstanceOf(FakeAuthSettings::class, $app->auth);
        $this->assertInstanceOf(FakePaymentSettings::class, $app->payment);
        $this->assertSame('auth.example.com', $app->auth->domain);
        $this->assertSame('live', $app->payment->mode);
    }

    public function test_root_settings_resolves_development_nested(): void
    {
        app()->detectEnvironment(fn () => 'local');

        $this->application()->singleton(FakeAppSettings::class, fn () => FakeAppSettings::resolve());

        $app = app(FakeAppSettings::class);

        $this->assertSame('dev.auth.example.com', $app->auth->domain);
        $this->assertSame('test', $app->payment->mode);
    }

    public function test_root_settings_resolves_staging_nested(): void
    {
        app()->detectEnvironment(fn () => 'staging');

        $this->application()->singleton(FakeAppSettings::class, fn () => FakeAppSettings::resolve());

        $app = app(FakeAppSettings::class);

        $this->assertSame('staging.auth.example.com', $app->auth->domain);
        // FakePaymentSettings has no staging() override, falls back to development
        $this->assertSame('test', $app->payment->mode);
    }

    public function test_root_settings_can_be_auto_registered(): void
    {
        app()->detectEnvironment(fn () => 'production');
        config()->set('env-settings.register', [FakeAppSettings::class]);
        $this->application()->register(EnvSettingsServiceProvider::class, true);

        $app = app(FakeAppSettings::class);

        $this->assertSame('auth.example.com', $app->auth->domain);
        $this->assertSame('live', $app->payment->mode);
    }

    // -------------------------------------------------------------------
    // envSettings() helper with registration
    // -------------------------------------------------------------------

    public function test_env_settings_helper_works_with_auto_registration(): void
    {
        app()->detectEnvironment(fn () => 'production');
        config()->set('env-settings.register', [FakeAuthSettings::class]);
        $this->application()->register(EnvSettingsServiceProvider::class, true);

        $settings = envSettings(FakeAuthSettings::class);

        $this->assertSame('auth.example.com', $settings->domain);
    }

    public function test_env_settings_helper_works_with_nested_root(): void
    {
        app()->detectEnvironment(fn () => 'production');
        config()->set('env-settings.register', [FakeAppSettings::class]);
        $this->application()->register(EnvSettingsServiceProvider::class, true);

        $app = envSettings(FakeAppSettings::class);

        $this->assertSame(10, $app->auth->timeout);
        $this->assertSame(5, $app->payment->retry_attempts);
    }
}
