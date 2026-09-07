<?php

declare(strict_types=1);

namespace HpWebDeveloper\LaravelEnvSettings\Tests\Commands;

use HpWebDeveloper\LaravelEnvSettings\EnvSettingsServiceProvider;
use HpWebDeveloper\LaravelEnvSettings\Tests\Fixtures\FakeAuthSettings;
use HpWebDeveloper\LaravelEnvSettings\Tests\Fixtures\FakeEnumSettings;
use HpWebDeveloper\LaravelEnvSettings\Tests\Fixtures\FakePaymentSettings;
use HpWebDeveloper\LaravelEnvSettings\Tests\Fixtures\FakeSecretSettings;
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
        $this->application()->register(EnvSettingsServiceProvider::class, true);

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

    // -------------------------------------------------------------------
    // --all: every environment side by side
    // -------------------------------------------------------------------

    /**
     * @param  class-string  $class
     */
    private function matrix(string $class): string
    {
        Artisan::call('env-settings:show', ['class' => $class, '--all' => true]);

        return Artisan::output();
    }

    public function test_all_prints_a_column_for_every_declared_environment(): void
    {
        $output = $this->matrix(FakeAuthSettings::class);

        foreach (['development', 'production', 'staging', 'testing'] as $environment) {
            $this->assertStringContainsString($environment, $output);
        }
    }

    public function test_all_shows_every_environment_value_in_one_table(): void
    {
        // The point of the flag: values that `show` alone would need four runs
        // and `diff` six to put in front of you.
        $output = $this->matrix(FakeAuthSettings::class);

        $this->assertStringContainsString('dev.auth.example.com', $output);
        $this->assertStringContainsString('auth.example.com', $output);
        $this->assertStringContainsString('staging.auth.example.com', $output);
    }

    public function test_all_marks_a_property_that_differs_somewhere(): void
    {
        $output = $this->matrix(FakeAuthSettings::class);

        $this->assertStringContainsString('domain *', $output);
        $this->assertStringContainsString('differs somewhere', $output);
    }

    public function test_all_leaves_a_property_that_is_identical_everywhere_unmarked(): void
    {
        // currency is EUR in every environment, so it does not vary and is a
        // candidate for not being a setting at all.
        $output = $this->matrix(FakeEnumSettings::class);

        $this->assertMatchesRegularExpression('/\|\s+currency\s+\|/', $output);
        $this->assertStringNotContainsString('currency *', $output);
        $this->assertStringContainsString('the same in every environment', $output);
    }

    public function test_all_names_the_properties_that_never_vary(): void
    {
        // Counting them was not actionable; naming them is. Each is a value
        // repeated in every factory that a constructor default could hold once.
        $output = $this->matrix(FakeEnumSettings::class);

        $this->assertStringContainsString('1 property is the same in every environment: currency', $output);
        $this->assertStringContainsString('env-settings:make --shared', $output);
    }

    public function test_all_does_not_suggest_defaults_when_every_property_varies(): void
    {
        $output = $this->matrix(FakeAuthSettings::class);

        $this->assertStringNotContainsString('the same in every environment', $output);
        $this->assertStringNotContainsString('--shared', $output);
    }

    public function test_all_renders_enums_as_their_values(): void
    {
        $output = $this->matrix(FakeEnumSettings::class);

        $this->assertStringContainsString('sandbox', $output);
        $this->assertStringContainsString('live', $output);
        $this->assertStringNotContainsString('[object:', $output);
    }

    public function test_all_masks_a_sensitive_property_but_still_marks_it_as_differing(): void
    {
        // Comparison happens on the real values, masking only on the way out —
        // otherwise every masked property would look identical everywhere.
        $output = $this->matrix(FakeSecretSettings::class);

        $this->assertStringContainsString('passphrase *', $output);
        $this->assertStringNotContainsString('dev-pass', $output);
        $this->assertStringNotContainsString('live-pass', $output);
        $this->assertStringContainsString('********', $output);
    }

    public function test_all_covers_every_registered_class_when_no_class_is_named(): void
    {
        config()->set('env-settings.register', [FakeAuthSettings::class, FakeEnumSettings::class]);

        $exitCode = Artisan::call('env-settings:show', ['--all' => true]);
        $output = Artisan::output();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('FakeAuthSettings', $output);
        $this->assertStringContainsString('FakeEnumSettings', $output);
    }

    public function test_without_the_flag_show_still_prints_only_the_current_environment(): void
    {
        app()->detectEnvironment(fn () => 'production');

        $output = $this->matrixless(FakeAuthSettings::class);

        $this->assertStringContainsString('Environment: production', $output);
        $this->assertStringNotContainsString('staging.auth.example.com', $output);
    }

    /**
     * @param  class-string  $class
     */
    private function matrixless(string $class): string
    {
        Artisan::call('env-settings:show', ['class' => $class]);

        return Artisan::output();
    }
}
