<?php

declare(strict_types=1);

namespace HpWebDeveloper\LaravelEnvSettings\Tests\Feature;

use HpWebDeveloper\LaravelEnvSettings\Tests\Fixtures\FakeSecretSettings;
use HpWebDeveloper\LaravelEnvSettings\Tests\TestCase;
use Illuminate\Support\Facades\Artisan;

class SensitiveMaskingTest extends TestCase
{
    private function show(): string
    {
        Artisan::call('env-settings:show', ['class' => FakeSecretSettings::class]);

        return Artisan::output();
    }

    private function diff(): string
    {
        Artisan::call('env-settings:diff', [
            'class' => FakeSecretSettings::class,
            'env1' => 'development',
            'env2' => 'production',
        ]);

        return Artisan::output();
    }

    // -------------------------------------------------------------------
    // #[Sensitive] — properties the name heuristic alone would have leaked
    // -------------------------------------------------------------------

    public function test_show_masks_a_marked_property_the_name_heuristic_would_miss(): void
    {
        app()->detectEnvironment(fn () => 'production');

        $output = $this->show();

        $this->assertStringNotContainsString('live-pass', $output);
        $this->assertStringNotContainsString('live-bearer', $output);
        $this->assertStringContainsString('********', $output);
    }

    public function test_diff_masks_marked_properties(): void
    {
        // Regression: env-settings:diff previously printed every value in full.
        $output = $this->diff();

        $this->assertStringNotContainsString('dev-pass', $output);
        $this->assertStringNotContainsString('live-pass', $output);
        $this->assertStringNotContainsString('dev-bearer', $output);
        $this->assertStringNotContainsString('live-bearer', $output);
    }

    public function test_diff_still_reports_a_masked_property_as_differing(): void
    {
        // The values are hidden, but the comparison runs on the real ones, so
        // the difference marker must still appear.
        $output = $this->diff();

        $this->assertMatchesRegularExpression('/passphrase\s+\*/', $output);
        $this->assertStringContainsString('values differ between environments', $output);
    }

    // -------------------------------------------------------------------
    // Fallback name heuristic still applies to unmarked properties
    // -------------------------------------------------------------------

    public function test_unmarked_property_matching_the_name_heuristic_is_still_masked(): void
    {
        app()->detectEnvironment(fn () => 'production');

        $this->assertStringNotContainsString('live-key', $this->show());
    }

    public function test_diff_masks_unmarked_properties_matching_the_heuristic(): void
    {
        $output = $this->diff();

        $this->assertStringNotContainsString('dev-key', $output);
        $this->assertStringNotContainsString('live-key', $output);
    }

    // -------------------------------------------------------------------
    // Values that must remain visible
    // -------------------------------------------------------------------

    public function test_ordinary_values_are_not_masked(): void
    {
        app()->detectEnvironment(fn () => 'production');

        $this->assertStringContainsString('us-east', $this->show());
    }

    public function test_an_empty_marked_property_is_not_masked(): void
    {
        app()->detectEnvironment(fn () => 'production');

        // Printing a mask for a value that was never set would be misleading.
        $output = $this->show();

        $this->assertMatchesRegularExpression('/optional_token\s*\|\s*string\s*\|\s*\|/', $output);
    }

    public function test_numeric_properties_matching_the_name_heuristic_are_not_masked(): void
    {
        // Regression: the fallback must stay string-only. max_tokens is an int
        // that happens to contain "token" — v1.0.0 displayed it, so must this.
        app()->detectEnvironment(fn () => 'production');

        $output = $this->show();

        $this->assertStringContainsString('8000', $output);
        $this->assertStringContainsString('12', $output);
    }

    public function test_a_marked_non_string_property_is_masked(): void
    {
        // The attribute is explicit, so it applies whatever the type.
        app()->detectEnvironment(fn () => 'production');

        $this->assertStringNotContainsString('2222', $this->show());
    }

    public function test_numeric_heuristic_properties_are_not_masked_in_diff(): void
    {
        $output = $this->diff();

        $this->assertStringContainsString('2000', $output);
        $this->assertStringContainsString('8000', $output);
    }

    public function test_to_array_returns_real_values(): void
    {
        // Masking is a display concern; serialising settings must keep working.
        $data = FakeSecretSettings::production()->toArray();

        $this->assertSame('live-pass', $data['passphrase']);
        $this->assertSame('live-bearer', $data['bearer']);
    }
}
