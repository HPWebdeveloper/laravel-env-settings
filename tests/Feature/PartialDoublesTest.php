<?php

declare(strict_types=1);

namespace HpWebDeveloper\LaravelEnvSettings\Tests\Feature;

use HpWebDeveloper\LaravelEnvSettings\EnvSettingsServiceProvider;
use HpWebDeveloper\LaravelEnvSettings\Tests\Fixtures\FakeAppSettings;
use HpWebDeveloper\LaravelEnvSettings\Tests\Fixtures\FakeAttributeSettings;
use HpWebDeveloper\LaravelEnvSettings\Tests\Fixtures\FakeAuthSettings;
use HpWebDeveloper\LaravelEnvSettings\Tests\TestCase;
use InvalidArgumentException;
use TypeError;

class PartialDoublesTest extends TestCase
{
    // -------------------------------------------------------------------
    // with() — copy an instance, changing only what matters
    // -------------------------------------------------------------------

    public function test_with_replaces_only_the_named_properties(): void
    {
        $copy = FakeAuthSettings::development()->with(['timeout' => 5]);

        $this->assertSame(5, $copy->timeout);
        $this->assertSame('dev.auth.example.com', $copy->domain);
        $this->assertSame('http://localhost:8000/callback', $copy->redirect_url);
        $this->assertFalse($copy->mfa_enabled);
    }

    public function test_with_replaces_several_properties_at_once(): void
    {
        $copy = FakeAuthSettings::development()->with([
            'timeout' => 5,
            'mfa_enabled' => true,
        ]);

        $this->assertSame(5, $copy->timeout);
        $this->assertTrue($copy->mfa_enabled);
    }

    public function test_with_leaves_the_original_untouched(): void
    {
        $original = FakeAuthSettings::development();

        $copy = $original->with(['timeout' => 5]);

        $this->assertSame(30, $original->timeout);
        $this->assertNotSame($original, $copy);
    }

    public function test_with_no_properties_returns_an_equal_copy(): void
    {
        $original = FakeAuthSettings::development();

        $copy = $original->with([]);

        $this->assertNotSame($original, $copy);
        $this->assertEquals($original->toArray(), $copy->toArray());
    }

    public function test_with_can_replace_a_nested_settings_object(): void
    {
        $settings = FakeAppSettings::development()->with([
            'auth' => FakeAuthSettings::production(),
        ]);

        $this->assertSame('auth.example.com', $settings->auth->domain);
        // The sibling still comes from development().
        $this->assertEquals(
            FakeAppSettings::development()->payment->toArray(),
            $settings->payment->toArray(),
        );
    }

    // -------------------------------------------------------------------
    // with() — a typo must be loud, not silent
    // -------------------------------------------------------------------

    public function test_with_rejects_an_unknown_property(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown property');

        FakeAuthSettings::development()->with(['timout' => 5]);
    }

    public function test_the_unknown_property_message_lists_the_known_ones(): void
    {
        try {
            FakeAuthSettings::development()->with(['timout' => 5]);
            $this->fail('Expected an InvalidArgumentException.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('timout', $e->getMessage());
            $this->assertStringContainsString('domain', $e->getMessage());
            $this->assertStringContainsString('mfa_enabled', $e->getMessage());
        }
    }

    public function test_the_message_is_pluralised_for_several_unknown_properties(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown properties');

        FakeAuthSettings::development()->with(['timout' => 5, 'domian' => 'x']);
    }

    public function test_with_surfaces_a_type_error_at_the_call_site(): void
    {
        $this->expectException(TypeError::class);

        FakeAuthSettings::development()->with(['timeout' => 'not-an-int']);
    }

    // -------------------------------------------------------------------
    // from() — resolve as another environment
    // -------------------------------------------------------------------

    public function test_from_resolves_the_named_environment(): void
    {
        app()->detectEnvironment(fn () => 'local');

        $this->assertSame('auth.example.com', FakeAuthSettings::from('production')->domain);
        $this->assertSame('staging.auth.example.com', FakeAuthSettings::from('staging')->domain);
    }

    public function test_from_restores_the_original_environment(): void
    {
        app()->detectEnvironment(fn () => 'local');

        FakeAuthSettings::from('production');

        $this->assertSame('local', app()->environment());
    }

    public function test_from_honours_the_environment_attribute(): void
    {
        app()->detectEnvironment(fn () => 'local');

        // qualityAssurance() is named unlike any APP_ENV value and serves both.
        $this->assertSame('qa', FakeAttributeSettings::from('qa')->tier);
        $this->assertSame('qa', FakeAttributeSettings::from('uat')->tier);
        $this->assertSame('prod', FakeAttributeSettings::from('canary')->tier);
    }

    // -------------------------------------------------------------------
    // fake() — bind a partial double into the container
    // -------------------------------------------------------------------

    public function test_fake_binds_the_double_into_the_container(): void
    {
        app()->detectEnvironment(fn () => 'local');

        FakeAuthSettings::fake(['timeout' => 5]);

        $this->assertSame(5, app(FakeAuthSettings::class)->timeout);
        $this->assertSame(5, envSettings(FakeAuthSettings::class)->timeout);
    }

    public function test_fake_keeps_the_unnamed_values_of_the_current_environment(): void
    {
        app()->detectEnvironment(fn () => 'local');

        FakeAuthSettings::fake(['timeout' => 5]);

        $this->assertSame('dev.auth.example.com', app(FakeAuthSettings::class)->domain);
    }

    public function test_fake_can_start_from_another_environment(): void
    {
        app()->detectEnvironment(fn () => 'local');

        FakeAuthSettings::fake(['timeout' => 5], from: 'production');

        $settings = app(FakeAuthSettings::class);

        $this->assertSame(5, $settings->timeout);
        $this->assertSame('auth.example.com', $settings->domain);
        $this->assertTrue($settings->mfa_enabled);
    }

    public function test_fake_overrides_a_registered_singleton(): void
    {
        app()->detectEnvironment(fn () => 'production');
        config()->set('env-settings.register', [FakeAuthSettings::class]);
        $this->application()->register(EnvSettingsServiceProvider::class, true);

        // Resolve once so the singleton is already built before faking.
        $this->assertSame(10, app(FakeAuthSettings::class)->timeout);

        FakeAuthSettings::fake(['timeout' => 5]);

        $this->assertSame(5, app(FakeAuthSettings::class)->timeout);
    }

    public function test_two_fakes_do_not_accumulate(): void
    {
        app()->detectEnvironment(fn () => 'local');

        FakeAuthSettings::fake(['timeout' => 5]);
        FakeAuthSettings::fake(['mfa_enabled' => true]);

        $settings = app(FakeAuthSettings::class);

        // The second call starts from the environment again, not from the first.
        $this->assertSame(30, $settings->timeout);
        $this->assertTrue($settings->mfa_enabled);
    }

    public function test_fake_returns_the_bound_instance(): void
    {
        app()->detectEnvironment(fn () => 'local');

        $returned = FakeAuthSettings::fake(['timeout' => 5]);

        $this->assertSame($returned, app(FakeAuthSettings::class));
    }

    public function test_fake_rejects_an_unknown_property(): void
    {
        app()->detectEnvironment(fn () => 'local');

        $this->expectException(InvalidArgumentException::class);

        FakeAuthSettings::fake(['timout' => 5]);
    }
}
