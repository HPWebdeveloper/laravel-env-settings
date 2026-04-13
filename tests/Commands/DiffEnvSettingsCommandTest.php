<?php

declare(strict_types=1);

namespace HpWebDeveloper\LaravelEnvSettings\Tests\Commands;

use HpWebDeveloper\LaravelEnvSettings\Tests\Fixtures\FakeAuthSettings;
use HpWebDeveloper\LaravelEnvSettings\Tests\Fixtures\FakePaymentSettings;
use HpWebDeveloper\LaravelEnvSettings\Tests\TestCase;
use Illuminate\Support\Facades\Artisan;

class DiffEnvSettingsCommandTest extends TestCase
{
    public function test_diff_shows_differences_between_environments(): void
    {
        $exitCode = Artisan::call('env-settings:diff', [
            'class' => FakeAuthSettings::class,
            'env1' => 'development',
            'env2' => 'production',
        ]);
        $output = Artisan::output();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('dev.auth.example.com', $output);
        $this->assertStringContainsString('auth.example.com', $output);
        $this->assertStringContainsString('*', $output);
    }

    public function test_diff_shows_no_differences_when_same_env(): void
    {
        $exitCode = Artisan::call('env-settings:diff', [
            'class' => FakePaymentSettings::class,
            'env1' => 'development',
            'env2' => 'development',
        ]);
        $output = Artisan::output();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('No differences found', $output);
    }

    public function test_diff_works_with_staging(): void
    {
        $exitCode = Artisan::call('env-settings:diff', [
            'class' => FakeAuthSettings::class,
            'env1' => 'staging',
            'env2' => 'production',
        ]);
        $output = Artisan::output();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('staging.auth.example.com', $output);
        $this->assertStringContainsString('auth.example.com', $output);
    }

    public function test_diff_fails_for_invalid_class(): void
    {
        $exitCode = Artisan::call('env-settings:diff', [
            'class' => 'NonExistent\\ClassName',
            'env1' => 'development',
            'env2' => 'production',
        ]);

        $this->assertSame(1, $exitCode);
    }

    public function test_diff_fails_for_invalid_environment_method(): void
    {
        $exitCode = Artisan::call('env-settings:diff', [
            'class' => FakeAuthSettings::class,
            'env1' => 'nonexistent_env',
            'env2' => 'production',
        ]);

        $this->assertSame(1, $exitCode);
    }

    public function test_diff_marks_different_values(): void
    {
        $exitCode = Artisan::call('env-settings:diff', [
            'class' => FakePaymentSettings::class,
            'env1' => 'development',
            'env2' => 'production',
        ]);
        $output = Artisan::output();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('values differ', $output);
    }
}
