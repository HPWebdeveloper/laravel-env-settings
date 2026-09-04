<?php

declare(strict_types=1);

namespace HpWebDeveloper\LaravelEnvSettings\Tests\Feature;

use HpWebDeveloper\LaravelEnvSettings\Tests\Fixtures\FakeCompleteSettings;
use HpWebDeveloper\LaravelEnvSettings\Tests\Fixtures\FakeIncompleteSettings;
use HpWebDeveloper\LaravelEnvSettings\Tests\TestCase;
use Illuminate\Support\Facades\Artisan;

class CheckCommandTest extends TestCase
{
    /**
     * @param  array<string, string>  $options
     * @return array{0: int, 1: string}
     */
    private function check(array $options = []): array
    {
        $code = Artisan::call('env-settings:check', $options);

        return [$code, Artisan::output()];
    }

    // -------------------------------------------------------------------
    // Reporting an unfinished environment
    // -------------------------------------------------------------------

    public function test_it_fails_when_a_factory_was_left_at_its_placeholders(): void
    {
        config()->set('env-settings.register', [FakeIncompleteSettings::class]);

        [$code, $output] = $this->check(['--env' => 'production']);

        $this->assertSame(1, $code, 'a non-zero exit is what makes this usable as a deploy gate');
        $this->assertStringContainsString('FakeIncompleteSettings', $output);
        $this->assertStringContainsString('domain', $output);
        $this->assertStringContainsString('timeout', $output);
    }

    public function test_it_says_which_environment_supplies_a_real_value(): void
    {
        config()->set('env-settings.register', [FakeIncompleteSettings::class]);

        [, $output] = $this->check(['--env' => 'production']);

        $this->assertStringContainsString('set in development()', $output);
    }

    public function test_it_reports_a_todo_left_in_the_value(): void
    {
        config()->set('env-settings.register', [FakeIncompleteSettings::class]);

        [, $output] = $this->check(['--env' => 'production']);

        $this->assertStringContainsString('webhook_url', $output);
        $this->assertStringContainsString('TODO', $output);
    }

    // -------------------------------------------------------------------
    // Not reporting things that are fine
    // -------------------------------------------------------------------

    public function test_a_value_that_is_empty_everywhere_is_not_reported(): void
    {
        // retry_attempts is 0 in every environment: deliberate, not forgotten.
        config()->set('env-settings.register', [FakeIncompleteSettings::class]);

        [, $output] = $this->check(['--env' => 'production']);

        $this->assertStringNotContainsString('retry_attempts', $output);
    }

    public function test_an_allow_empty_property_is_never_reported(): void
    {
        config()->set('env-settings.register', [FakeIncompleteSettings::class]);

        [, $output] = $this->check(['--env' => 'production']);

        $this->assertStringNotContainsString('path_prefix', $output);
    }

    public function test_it_passes_when_every_class_is_complete(): void
    {
        config()->set('env-settings.register', [FakeCompleteSettings::class]);

        [$code, $output] = $this->check(['--env' => 'production']);

        $this->assertSame(0, $code);
        $this->assertStringContainsString('complete', $output);
    }

    public function test_the_environment_being_checked_passes_when_it_is_the_filled_one(): void
    {
        // development() is complete; only production() was forgotten.
        config()->set('env-settings.register', [FakeIncompleteSettings::class]);

        [, $output] = $this->check(['--env' => 'development']);

        $this->assertStringNotContainsString('domain', $output);
        $this->assertStringNotContainsString('timeout', $output);
    }

    // -------------------------------------------------------------------
    // Command surface
    // -------------------------------------------------------------------

    public function test_it_defaults_to_the_current_environment(): void
    {
        app()->detectEnvironment(fn () => 'production');
        config()->set('env-settings.register', [FakeIncompleteSettings::class]);

        [$code, $output] = $this->check();

        $this->assertSame(1, $code);
        $this->assertStringContainsString('production', $output);
    }

    public function test_it_can_check_a_single_class_without_registration(): void
    {
        config()->set('env-settings.register', []);

        [$code, $output] = $this->check([
            'class' => FakeIncompleteSettings::class,
            '--env' => 'production',
        ]);

        $this->assertSame(1, $code);
        $this->assertStringContainsString('FakeIncompleteSettings', $output);
    }

    public function test_it_fails_on_a_class_that_is_not_a_settings_class(): void
    {
        [$code, $output] = $this->check(['class' => 'Totally\\Missing\\Settings']);

        $this->assertSame(1, $code);
        $this->assertStringContainsString('not a valid EnvironmentSettings subclass', $output);
    }

    public function test_it_warns_when_nothing_is_registered(): void
    {
        config()->set('env-settings.register', []);

        [$code, $output] = $this->check(['--env' => 'production']);

        // Nothing to check is not a failure — a fresh install must not break CI.
        $this->assertSame(0, $code);
        $this->assertStringContainsString('No settings classes registered', $output);
    }

    public function test_a_non_array_register_config_does_not_crash(): void
    {
        config()->set('env-settings.register', 'not-an-array');

        [$code] = $this->check(['--env' => 'production']);

        $this->assertSame(0, $code);
    }
}
