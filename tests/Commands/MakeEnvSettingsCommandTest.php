<?php

declare(strict_types=1);

namespace HpWebDeveloper\LaravelEnvSettings\Tests\Commands;

use HpWebDeveloper\LaravelEnvSettings\Tests\TestCase;
use Illuminate\Support\Facades\File;

class MakeEnvSettingsCommandTest extends TestCase
{
    private string $outputPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->outputPath = sys_get_temp_dir().'/env-settings-test-'.uniqid();
        mkdir($this->outputPath, 0755, true);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->outputPath);
        parent::tearDown();
    }

    public function test_it_creates_a_settings_class_file(): void
    {
        $this->artisan('env-settings:make', [
            'name' => 'AuthSettings',
            '--path' => $this->outputPath,
        ])->assertSuccessful();

        $this->assertFileExists($this->outputPath.'/AuthSettings.php');
    }

    public function test_generated_file_contains_correct_class_name(): void
    {
        $this->artisan('env-settings:make', [
            'name' => 'PaymentSettings',
            '--path' => $this->outputPath,
        ])->assertSuccessful();

        $content = file_get_contents($this->outputPath.'/PaymentSettings.php');

        $this->assertStringContainsString('class PaymentSettings extends EnvironmentSettings', $content);
    }

    public function test_generated_file_has_development_and_production_methods(): void
    {
        $this->artisan('env-settings:make', [
            'name' => 'QueueSettings',
            '--path' => $this->outputPath,
        ])->assertSuccessful();

        $content = file_get_contents($this->outputPath.'/QueueSettings.php');

        $this->assertStringContainsString('public static function development(): static', $content);
        $this->assertStringContainsString('public static function production(): static', $content);
    }

    public function test_generated_file_has_default_example_property_when_no_properties_given(): void
    {
        $this->artisan('env-settings:make', [
            'name' => 'BasicSettings',
            '--path' => $this->outputPath,
        ])->assertSuccessful();

        $content = file_get_contents($this->outputPath.'/BasicSettings.php');

        $this->assertStringContainsString('public string $example', $content);
    }

    public function test_properties_option_generates_typed_constructor(): void
    {
        $this->artisan('env-settings:make', [
            'name' => 'ApiSettings',
            '--properties' => 'domain:string,timeout:int,enabled:bool',
            '--path' => $this->outputPath,
        ])->assertSuccessful();

        $content = file_get_contents($this->outputPath.'/ApiSettings.php');

        $this->assertStringContainsString('public string $domain', $content);
        $this->assertStringContainsString('public int $timeout', $content);
        $this->assertStringContainsString('public bool $enabled', $content);
    }

    public function test_properties_option_generates_correct_defaults_in_factory_methods(): void
    {
        $this->artisan('env-settings:make', [
            'name' => 'MixedSettings',
            '--properties' => 'name:string,count:int,rate:float,active:bool,tags:array',
            '--path' => $this->outputPath,
        ])->assertSuccessful();

        $content = file_get_contents($this->outputPath.'/MixedSettings.php');

        $this->assertStringContainsString("name: ''", $content);
        $this->assertStringContainsString('count: 0', $content);
        $this->assertStringContainsString('rate: 0.0', $content);
        $this->assertStringContainsString('active: false', $content);
        $this->assertStringContainsString('tags: []', $content);
    }

    public function test_it_fails_if_file_already_exists(): void
    {
        // Create the file first
        file_put_contents($this->outputPath.'/ExistingSettings.php', '<?php // existing');

        $this->artisan('env-settings:make', [
            'name' => 'ExistingSettings',
            '--path' => $this->outputPath,
        ])->assertFailed();
    }

    public function test_generated_file_has_correct_use_statement(): void
    {
        $this->artisan('env-settings:make', [
            'name' => 'TestSettings',
            '--path' => $this->outputPath,
        ])->assertSuccessful();

        $content = file_get_contents($this->outputPath.'/TestSettings.php');

        $this->assertStringContainsString('use HpWebDeveloper\LaravelEnvSettings\EnvironmentSettings;', $content);
    }
}
