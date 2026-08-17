<?php

declare(strict_types=1);

namespace HpWebDeveloper\LaravelEnvSettings\Tests\Commands;

use HpWebDeveloper\LaravelEnvSettings\Tests\Fixtures\FakeAuthSettings;
use HpWebDeveloper\LaravelEnvSettings\Tests\Fixtures\FakePaymentSettings;
use HpWebDeveloper\LaravelEnvSettings\Tests\TestCase;

/**
 * Covers the paths reachable only through `select()`.
 *
 * The non-interactive tests always pass the class and both environments as
 * arguments, so nothing there exercises the prompts or the mapping of a
 * selected option back to a class name or environment.
 *
 * Laravel routes prompts through Symfony's `choice()` question while running
 * tests, which is what `expectsChoice()` drives.
 */
class DiffEnvSettingsCommandInteractiveTest extends TestCase
{
    private const CLASS_PROMPT = 'Which settings class would you like to compare?';

    public function test_it_prompts_for_the_class_when_none_is_given(): void
    {
        config()->set('env-settings.register', [
            FakeAuthSettings::class,
            FakePaymentSettings::class,
        ]);

        $this->artisan('env-settings:diff')
            ->expectsChoice(self::CLASS_PROMPT, FakeAuthSettings::class, [
                FakeAuthSettings::class => 'FakeAuthSettings ('.FakeAuthSettings::class.')',
                FakePaymentSettings::class => 'FakePaymentSettings ('.FakePaymentSettings::class.')',
            ])
            ->expectsChoice('First environment', 'development', ['development', 'production', 'staging', 'testing'])
            ->expectsChoice('Second environment', 'production', ['production', 'staging', 'testing'])
            ->expectsOutputToContain('FakeAuthSettings')
            ->assertSuccessful();
    }

    public function test_it_maps_a_later_choice_back_to_the_right_class(): void
    {
        // Guards the key-to-class mapping: returning the first entry
        // regardless of the choice would still pass the test above.
        config()->set('env-settings.register', [
            FakeAuthSettings::class,
            FakePaymentSettings::class,
        ]);

        $this->artisan('env-settings:diff')
            ->expectsChoice(self::CLASS_PROMPT, FakePaymentSettings::class, [
                FakeAuthSettings::class => 'FakeAuthSettings ('.FakeAuthSettings::class.')',
                FakePaymentSettings::class => 'FakePaymentSettings ('.FakePaymentSettings::class.')',
            ])
            ->expectsChoice('First environment', 'development', ['development', 'production', 'staging', 'testing'])
            ->expectsChoice('Second environment', 'production', ['production', 'staging', 'testing'])
            ->expectsOutputToContain('FakePaymentSettings')
            ->assertSuccessful();
    }

    public function test_it_prompts_for_both_environments_when_only_a_class_is_given(): void
    {
        $this->artisan('env-settings:diff', ['class' => FakeAuthSettings::class])
            ->expectsChoice('First environment', 'development', ['development', 'production', 'staging', 'testing'])
            ->expectsChoice('Second environment', 'production', ['production', 'staging', 'testing'])
            ->expectsOutputToContain('Comparing development vs production')
            ->assertSuccessful();
    }

    public function test_the_second_environment_prompt_excludes_the_first_choice(): void
    {
        // `array_diff` removes the first selection, so `production` is absent
        // from the second list once it has been chosen.
        $this->artisan('env-settings:diff', ['class' => FakeAuthSettings::class])
            ->expectsChoice('First environment', 'production', ['development', 'production', 'staging', 'testing'])
            ->expectsChoice('Second environment', 'staging', ['development', 'staging', 'testing'])
            ->expectsOutputToContain('Comparing production vs staging')
            ->assertSuccessful();
    }

    public function test_an_invalid_class_argument_falls_back_to_the_prompt(): void
    {
        config()->set('env-settings.register', [FakeAuthSettings::class]);

        $this->artisan('env-settings:diff', ['class' => 'Totally\\Missing\\Settings'])
            ->expectsOutputToContain('falling back to interactive selection')
            ->expectsChoice(self::CLASS_PROMPT, FakeAuthSettings::class, [
                FakeAuthSettings::class => 'FakeAuthSettings ('.FakeAuthSettings::class.')',
            ])
            ->expectsChoice('First environment', 'development', ['development', 'production', 'staging', 'testing'])
            ->expectsChoice('Second environment', 'production', ['production', 'staging', 'testing'])
            ->assertSuccessful();
    }

    public function test_it_reports_when_nothing_is_registered_to_choose_from(): void
    {
        config()->set('env-settings.register', []);

        $this->artisan('env-settings:diff')
            ->expectsOutputToContain('No settings classes registered')
            ->assertSuccessful();
    }

    public function test_it_reports_when_the_register_config_is_not_an_array(): void
    {
        // A scalar here used to reach `array_filter()` and raise a TypeError.
        config()->set('env-settings.register', 'not-an-array');

        $this->artisan('env-settings:diff')
            ->expectsOutputToContain('No settings classes registered')
            ->assertSuccessful();
    }

    public function test_registered_entries_that_are_not_settings_classes_are_skipped(): void
    {
        config()->set('env-settings.register', [
            'Some\\Missing\\Class',
            123,
            FakeAuthSettings::class,
        ]);

        $this->artisan('env-settings:diff')
            ->expectsChoice(self::CLASS_PROMPT, FakeAuthSettings::class, [
                FakeAuthSettings::class => 'FakeAuthSettings ('.FakeAuthSettings::class.')',
            ])
            ->expectsChoice('First environment', 'development', ['development', 'production', 'staging', 'testing'])
            ->expectsChoice('Second environment', 'production', ['production', 'staging', 'testing'])
            ->assertSuccessful();
    }
}
