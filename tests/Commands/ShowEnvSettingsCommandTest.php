<?php

declare(strict_types=1);

namespace HpWebDeveloper\LaravelEnvSettings\Tests\Commands;

use HpWebDeveloper\LaravelEnvSettings\EnvSettingsServiceProvider;
use HpWebDeveloper\LaravelEnvSettings\Tests\Fixtures\FakeAppSettings;
use HpWebDeveloper\LaravelEnvSettings\Tests\Fixtures\FakeAuthSettings;
use HpWebDeveloper\LaravelEnvSettings\Tests\Fixtures\FakeComposedSettings;
use HpWebDeveloper\LaravelEnvSettings\Tests\Fixtures\FakeDefaultsSettings;
use HpWebDeveloper\LaravelEnvSettings\Tests\Fixtures\FakeEnumSettings;
use HpWebDeveloper\LaravelEnvSettings\Tests\Fixtures\FakeObjectValuedSettings;
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

    public function test_all_prints_a_column_for_every_environment_the_class_defines(): void
    {
        // FakeAuthSettings defines staging() but inherits testing(), whose
        // column would only repeat development's.
        $output = $this->matrix(FakeAuthSettings::class);

        $this->assertMatchesRegularExpression('/\] — development, production, staging$/m', $output);
        $this->assertDoesNotMatchRegularExpression('/\|\s+testing\s+\|/', $output);
    }

    public function test_all_names_the_factories_that_fall_back_to_development(): void
    {
        // Dropping their columns must not hide that staging runs development's
        // values, so the factories a class inherits are named instead.
        $this->assertStringContainsString(
            'Not shown: staging() and testing(), which fall back to development().',
            $this->matrix(FakeSecretSettings::class),
        );
        $this->assertStringContainsString(
            'Not shown: testing(), which falls back to development().',
            $this->matrix(FakeAuthSettings::class),
        );
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
        // currency is EUR in every environment, so it does not vary — a value
        // a constructor default could hold once.
        $output = $this->matrix(FakeEnumSettings::class);

        $this->assertMatchesRegularExpression('/\|\s+currency\s+\|/', $output);
        $this->assertStringNotContainsString('currency *', $output);
        $this->assertStringContainsString('repeated with the same value in every environment', $output);
    }

    public function test_all_names_the_properties_that_never_vary(): void
    {
        // Counting them was not actionable; naming them is. Each is a value
        // repeated in every factory that a constructor default could hold once.
        $output = $this->matrix(FakeEnumSettings::class);

        $this->assertStringContainsString('1 property is repeated with the same value in every environment: currency', $output);
        $this->assertStringContainsString('Each can be a constructor default instead', $output);
    }

    public function test_all_does_not_suggest_defaults_when_every_property_varies(): void
    {
        $output = $this->matrix(FakeAuthSettings::class);

        $this->assertStringNotContainsString('repeated with the same value', $output);
        $this->assertStringNotContainsString('constructor default', $output);
    }

    public function test_all_does_not_name_a_property_that_already_holds_its_default(): void
    {
        // currency, regions and timezone never vary either, but they hold their
        // declared defaults — written once already. Naming them would repeat
        // advice that has been taken, so only locale is left.
        $output = $this->matrix(FakeDefaultsSettings::class);

        $this->assertStringContainsString('1 property is repeated with the same value in every environment: locale', $output);
    }

    public function test_all_names_a_default_that_every_factory_overrides_with_the_same_value(): void
    {
        // locale has a default of 'en', but every factory passes 'de': the
        // literal is still written out once per factory, so the advice stands.
        $output = $this->matrix(FakeDefaultsSettings::class);

        $this->assertMatchesRegularExpression('/repeated with the same value in every environment: .*\blocale\b/', $output);
        $this->assertStringContainsString('Each can be a constructor default instead', $output);
    }

    public function test_all_does_not_suggest_a_default_for_a_nested_class(): void
    {
        // payment is the same in every environment, but a child settings class
        // is built by its own factories — not a literal for the signature.
        $output = $this->matrix(FakeComposedSettings::class);

        $this->assertStringNotContainsString('constructor default', $output);
    }

    public function test_all_marks_a_nested_class_that_differs_inside(): void
    {
        // Every cell reads [nested: Class], so comparing the rendered strings
        // reported a composed class as never varying.
        $output = $this->matrix(FakeAppSettings::class);

        $this->assertStringContainsString('auth *', $output);
        $this->assertStringContainsString('payment *', $output);
        $this->assertStringNotContainsString('No property varies', $output);
        $this->assertStringContainsString('A nested class marked * differs inside', $output);
    }

    public function test_all_leaves_a_nested_class_unmarked_when_it_holds_the_same_values(): void
    {
        // Equal values in separate instances: compared by state, not identity.
        $output = $this->matrix(FakeComposedSettings::class);

        $this->assertStringContainsString('auth *', $output);
        $this->assertMatchesRegularExpression('/\|\s+payment\s+\|/', $output);
    }

    public function test_all_marks_an_object_that_differs(): void
    {
        // starts_at holds a different date in each environment; both render as
        // [object: DateTimeImmutable], which hid the difference.
        $this->assertStringContainsString('starts_at *', $this->matrix(FakeObjectValuedSettings::class));
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
