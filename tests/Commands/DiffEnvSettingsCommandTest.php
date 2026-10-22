<?php

declare(strict_types=1);

namespace HpWebDeveloper\LaravelEnvSettings\Tests\Commands;

use HpWebDeveloper\LaravelEnvSettings\Tests\Fixtures\FakeAppSettings;
use HpWebDeveloper\LaravelEnvSettings\Tests\Fixtures\FakeAuthSettings;
use HpWebDeveloper\LaravelEnvSettings\Tests\Fixtures\FakeComposedSettings;
use HpWebDeveloper\LaravelEnvSettings\Tests\Fixtures\FakeObjectValuedSettings;
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

    /**
     * @return string the command's output
     */
    private function diffObjectValued(): string
    {
        Artisan::call('env-settings:diff', [
            'class' => FakeObjectValuedSettings::class,
            'env1' => 'development',
            'env2' => 'production',
        ]);

        return Artisan::output();
    }

    public function test_diff_renders_an_object_that_cannot_be_cast_to_string(): void
    {
        // Casting one raised `Error: Object of class DateTimeImmutable could not
        // be converted to string`, taking the whole command down.
        $this->assertStringContainsString('[object: DateTimeImmutable]', $this->diffObjectValued());
    }

    public function test_diff_distinguishes_null_from_an_empty_string(): void
    {
        // Both used to render as '', so the comparison saw them as equal and the
        // row went unmarked — the command reporting no difference where one
        // exists is worse than the crash above.
        $output = $this->diffObjectValued();

        $this->assertStringContainsString('null', $output);
        $this->assertStringContainsString('label *', $output);
    }

    public function test_diff_marks_an_object_that_differs(): void
    {
        // Both dates render as [object: DateTimeImmutable], so comparing the
        // rendered strings missed that they differ.
        $this->assertStringContainsString('starts_at *', $this->diffObjectValued());
    }

    /**
     * @param  class-string  $class
     * @return string the command's output
     */
    private function diffDevelopmentAndProduction(string $class): string
    {
        Artisan::call('env-settings:diff', [
            'class' => $class,
            'env1' => 'development',
            'env2' => 'production',
        ]);

        return Artisan::output();
    }

    public function test_diff_marks_a_nested_class_that_differs_inside(): void
    {
        // Every child differs, yet both columns read [nested: Class], and the
        // command reported "No differences found".
        $output = $this->diffDevelopmentAndProduction(FakeAppSettings::class);

        $this->assertStringContainsString('auth *', $output);
        $this->assertStringContainsString('payment *', $output);
        $this->assertStringNotContainsString('No differences found', $output);
        $this->assertStringContainsString('A nested class marked * differs inside', $output);
    }

    public function test_diff_leaves_a_nested_class_unmarked_when_it_holds_the_same_values(): void
    {
        // Equal values in separate instances: compared by state, not identity.
        $output = $this->diffDevelopmentAndProduction(FakeComposedSettings::class);

        $this->assertStringContainsString('auth *', $output);
        $this->assertMatchesRegularExpression('/\|\s+payment\s+\|/', $output);
    }
}
