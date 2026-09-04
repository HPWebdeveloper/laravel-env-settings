<?php

declare(strict_types=1);

namespace HpWebDeveloper\LaravelEnvSettings\Tests\Feature;

use HpWebDeveloper\LaravelEnvSettings\Tests\Fixtures\FakeEnumSettings;
use HpWebDeveloper\LaravelEnvSettings\Tests\Fixtures\PaymentMode;
use HpWebDeveloper\LaravelEnvSettings\Tests\Fixtures\Tier;
use HpWebDeveloper\LaravelEnvSettings\Tests\TestCase;
use Illuminate\Support\Facades\Artisan;

class EnumValuedSettingsTest extends TestCase
{
    private function show(): string
    {
        Artisan::call('env-settings:show', ['class' => FakeEnumSettings::class]);

        return Artisan::output();
    }

    // -------------------------------------------------------------------
    // Resolution
    // -------------------------------------------------------------------

    public function test_an_enum_property_resolves_per_environment(): void
    {
        app()->detectEnvironment(fn () => 'production');
        $this->assertSame(PaymentMode::Live, FakeEnumSettings::resolve()->mode);

        app()->detectEnvironment(fn () => 'local');
        $this->assertSame(PaymentMode::Sandbox, FakeEnumSettings::resolve()->mode);
    }

    // -------------------------------------------------------------------
    // toArray() — the payload must stay JSON-encodable
    // -------------------------------------------------------------------

    public function test_a_backed_enum_is_unwrapped_to_its_value(): void
    {
        $this->assertSame('live', FakeEnumSettings::production()->toArray()['mode']);
    }

    public function test_a_pure_enum_is_unwrapped_to_its_name(): void
    {
        $this->assertSame('High', FakeEnumSettings::production()->toArray()['tier']);
    }

    public function test_enums_inside_arrays_are_unwrapped(): void
    {
        $this->assertSame(['live', 'sandbox'], FakeEnumSettings::production()->toArray()['accepted']);
    }

    public function test_the_whole_payload_survives_json_encode(): void
    {
        // Regression: a pure enum has no default serialisation, so leaving it in
        // place made json_encode return false for the entire settings tree.
        $json = json_encode(FakeEnumSettings::production()->toArray());

        $this->assertIsString($json);
        $this->assertJson($json);
        $this->assertStringContainsString('"mode":"live"', $json);
        $this->assertStringContainsString('"tier":"High"', $json);
    }

    public function test_non_enum_values_are_untouched(): void
    {
        $this->assertSame('EUR', FakeEnumSettings::production()->toArray()['currency']);
    }

    // -------------------------------------------------------------------
    // Console output
    // -------------------------------------------------------------------

    public function test_show_prints_enum_values_rather_than_the_object(): void
    {
        app()->detectEnvironment(fn () => 'production');

        $output = $this->show();

        $this->assertStringContainsString('live', $output);
        $this->assertStringContainsString('High', $output);
        $this->assertStringNotContainsString('[object:', $output);
    }

    public function test_diff_prints_enum_values_and_marks_the_difference(): void
    {
        Artisan::call('env-settings:diff', [
            'class' => FakeEnumSettings::class,
            'env1' => 'development',
            'env2' => 'production',
        ]);

        $output = Artisan::output();

        $this->assertStringNotContainsString('[object:', $output);
        $this->assertStringContainsString('sandbox', $output);
        $this->assertStringContainsString('live', $output);
        // mode differs between the two environments, so it must be flagged.
        $this->assertMatchesRegularExpression('/mode\s+\*/', $output);
    }

    public function test_an_identical_enum_value_is_not_flagged_as_differing(): void
    {
        Artisan::call('env-settings:diff', [
            'class' => FakeEnumSettings::class,
            'env1' => 'production',
            'env2' => 'production',
        ]);

        $this->assertStringContainsString('No differences found', Artisan::output());
    }

    public function test_enum_cases_are_identical_across_environments(): void
    {
        // The enum defines the vocabulary; the settings class chooses from it.
        // Cases never vary by environment — only which one is selected does.
        app()->detectEnvironment(fn () => 'local');
        $dev = FakeEnumSettings::resolve()->mode;

        app()->detectEnvironment(fn () => 'production');
        $prod = FakeEnumSettings::resolve()->mode;

        $this->assertNotSame($dev, $prod);
        $this->assertSame(PaymentMode::Sandbox, PaymentMode::from('sandbox'));
        $this->assertSame(Tier::High, FakeEnumSettings::production()->tier);
    }
}
