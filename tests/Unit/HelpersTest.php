<?php

declare(strict_types=1);

namespace HpWebDeveloper\LaravelEnvSettings\Tests\Unit;

use HpWebDeveloper\LaravelEnvSettings\Tests\Fixtures\FakeAuthSettings;
use HpWebDeveloper\LaravelEnvSettings\Tests\TestCase;

class HelpersTest extends TestCase
{
    public function test_env_settings_helper_resolves_class(): void
    {
        $this->app->singleton(FakeAuthSettings::class, fn () => FakeAuthSettings::development());

        $settings = envSettings(FakeAuthSettings::class);

        $this->assertInstanceOf(FakeAuthSettings::class, $settings);
        $this->assertSame('dev.auth.example.com', $settings->domain);
    }
}
