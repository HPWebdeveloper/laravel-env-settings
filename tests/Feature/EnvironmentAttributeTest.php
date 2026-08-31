<?php

declare(strict_types=1);

namespace HpWebDeveloper\LaravelEnvSettings\Tests\Feature;

use HpWebDeveloper\LaravelEnvSettings\Tests\Fixtures\FakeAttributeSettings;
use HpWebDeveloper\LaravelEnvSettings\Tests\Fixtures\FakeAuthSettings;
use HpWebDeveloper\LaravelEnvSettings\Tests\TestCase;

class EnvironmentAttributeTest extends TestCase
{
    private function resolveIn(string $appEnv): string
    {
        app()->detectEnvironment(fn () => $appEnv);

        return FakeAttributeSettings::resolve()->tier;
    }

    public function test_it_resolves_an_environment_named_by_the_attribute(): void
    {
        $this->assertSame('prod', $this->resolveIn('production'));
    }

    public function test_one_method_can_serve_several_environments(): void
    {
        // 'prod' and 'canary' both point at production() without any config.
        $this->assertSame('prod', $this->resolveIn('prod'));
        $this->assertSame('prod', $this->resolveIn('canary'));
    }

    public function test_the_method_name_need_not_match_the_environment(): void
    {
        // qualityAssurance() is reached from APP_ENV values that share no name
        // with it — impossible today without an environment_map entry.
        $this->assertSame('qa', $this->resolveIn('qa'));
        $this->assertSame('qa', $this->resolveIn('uat'));
    }

    public function test_an_unmarked_environment_still_falls_back(): void
    {
        $this->assertSame('dev', $this->resolveIn('something-unmapped'));
    }

    public function test_the_attribute_wins_over_the_environment_map(): void
    {
        // The class states which method serves 'canary'; a map that disagrees
        // must not silently redirect it.
        config()->set('env-settings.environment_map', ['canary' => 'development']);

        $this->assertSame('prod', $this->resolveIn('canary'));
    }

    public function test_classes_without_attributes_are_unaffected(): void
    {
        // The existing map-based path must behave exactly as before.
        app()->detectEnvironment(fn () => 'local');

        $this->assertSame('dev.auth.example.com', FakeAuthSettings::resolve()->domain);

        app()->detectEnvironment(fn () => 'production');

        $this->assertSame('auth.example.com', FakeAuthSettings::resolve()->domain);
    }

    public function test_the_environment_map_still_applies_to_unmarked_environments(): void
    {
        // 'staging' is not declared on the fixture, so the map decides.
        config()->set('env-settings.environment_map', ['staging' => 'production']);

        $this->assertSame('prod', $this->resolveIn('staging'));
    }

    public function test_a_subclass_inherits_the_mapping_when_it_redeclares_a_marked_method(): void
    {
        // PHP does not inherit attributes onto an overridden method. Without
        // walking the hierarchy the mapping vanishes and 'uat' silently
        // resolves to development — exactly what a local override would hit.
        config()->set('env-settings.override', true);
        config()->set('env-settings.override_path', __DIR__.'/../Fixtures/Overrides');
        config()->set('env-settings.override_namespace', 'HpWebDeveloper\\LaravelEnvSettings\\Tests\\Fixtures\\Overrides');

        app()->detectEnvironment(fn () => 'uat');

        // The override supplies the values; the mapping comes from the parent.
        $this->assertSame('overridden-qa', FakeAttributeSettings::resolve()->tier);
    }

    public function test_a_marked_method_that_is_not_a_usable_factory_is_ignored(): void
    {
        // Calling a non-static or non-public method as a factory raises an
        // Error, so a misplaced attribute must fall through rather than crash.
        $this->assertSame('dev', $this->resolveIn('bad-instance'));
        $this->assertSame('dev', $this->resolveIn('bad-protected'));
    }

    public function test_repeated_resolution_is_consistent(): void
    {
        // The resolver caches the reflected map per class; a second call in a
        // different environment must not serve the first one's answer.
        $this->assertSame('qa', $this->resolveIn('uat'));
        $this->assertSame('prod', $this->resolveIn('canary'));
        $this->assertSame('qa', $this->resolveIn('qa'));
    }
}
