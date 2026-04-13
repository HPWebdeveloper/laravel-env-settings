<?php

declare(strict_types=1);

namespace HpWebDeveloper\LaravelEnvSettings\Tests\Commands;

use HpWebDeveloper\LaravelEnvSettings\EnvSettingsServiceProvider;
use HpWebDeveloper\LaravelEnvSettings\Tests\Fixtures\FakeAuthSettings;
use HpWebDeveloper\LaravelEnvSettings\Tests\Fixtures\FakePaymentSettings;
use HpWebDeveloper\LaravelEnvSettings\Tests\TestCase;
use Illuminate\Support\Facades\Artisan;

class ShowEnvSettingsCommandTest extends TestCase
{
    public function test_show_displays_resolved_settings_for_a_class(): void
    {
        app()->detectEnvironment(fn () => 'production');

        $exitCode = Artisan::call('env-settings:show', [
            'class' => FakeAuthSettings::class,
        ]);
        $output = Artisan::output();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('auth.example.com', $output);
        $this->assertStringContainsString('domain', $output);
    }

    public function test_show_displays_all_registered_classes(): void
    {
        app()->detectEnvironment(fn () => 'production');
        config()->set('env-settings.register', [
            FakeAuthSettings::class,
            FakePaymentSettings::class,
        ]);
        $this->app->register(EnvSettingsServiceProvider::class, true);

        $exitCode = Artisan::call('env-settings:show');
        $output = Artisan::output();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('FakeAuthSettings', $output);
        $this->assertStringContainsString('FakePaymentSettings', $output);
    }

    public function test_show_warns_when_no_classes_registered(): void
    {
        config()->set('env-settings.register', []);

        $exitCode = Artisan::call('env-settings:show');
        $output = Artisan::output();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('No settings classes registered', $output);
    }

    public function test_show_fails_for_invalid_class(): void
    {
        $exitCode = Artisan::call('env-settings:show', [
            'class' => 'NonExistent\\ClassName',
        ]);

        $this->assertSame(1, $exitCode);
    }

    public function test_show_displays_boolean_values_correctly(): void
    {
        app()->detectEnvironment(fn () => 'production');

        $exitCode = Artisan::call('env-settings:show', [
            'class' => FakeAuthSettings::class,
        ]);
        $output = Artisan::output();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('true', $output);
    }
}
