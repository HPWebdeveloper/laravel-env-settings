<?php

declare(strict_types=1);

namespace HpWebDeveloper\LaravelEnvSettings\Tests\Commands;

use HpWebDeveloper\LaravelEnvSettings\Tests\TestCase;
use Illuminate\Support\Facades\File;

class MakeEnvSettingsCommandTest extends TestCase
{
    private string $outputPath;

    /**
     * Directories created inside the application root by a test.
     *
     * Tracked here rather than cleaned inline so that a failing assertion
     * cannot leak generated classes into the next test's run.
     *
     * @var list<string>
     */
    private array $appDirs = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->outputPath = sys_get_temp_dir().'/env-settings-test-'.uniqid();
        mkdir($this->outputPath, 0755, true);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->outputPath);

        foreach ($this->appDirs as $dir) {
            File::deleteDirectory($dir);
        }

        $this->appDirs = [];

        parent::tearDown();
    }

    /**
     * Register an application-root directory for removal in tearDown.
     */
    private function appDir(string $relative): string
    {
        $this->appDirs[] = app_path(explode('/', $relative)[0]);

        return app_path($relative);
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

    // -------------------------------------------------------------------
    // Namespace resolution
    // -------------------------------------------------------------------

    public function test_it_uses_the_configured_namespace_when_no_path_is_given(): void
    {
        config()->set('env-settings.class_namespace', 'App\\Settings');

        $path = $this->appDir('Settings');

        $this->artisan('env-settings:make', ['name' => 'DefaultSettings'])->assertSuccessful();

        $this->assertSame('App\\Settings', $this->namespaceOf($path.'/DefaultSettings.php'));
    }

    public function test_it_derives_the_namespace_from_a_path_inside_the_app_root(): void
    {
        // A modular layout: the class has to be namespaced to match where it
        // lands, otherwise PSR-4 can never autoload it.
        $path = $this->appDir('Modules/Billing/Settings');

        $this->artisan('env-settings:make', [
            'name' => 'BillingSettings',
            '--path' => $path,
        ])->assertSuccessful();

        $this->assertSame('App\\Modules\\Billing\\Settings', $this->namespaceOf($path.'/BillingSettings.php'));
    }

    public function test_derived_namespace_collapses_relative_path_segments(): void
    {
        $this->appDirs[] = app_path('Reporting');
        $this->appDirs[] = app_path('Settings');

        $this->artisan('env-settings:make', [
            'name' => 'ReportSettings',
            '--path' => app_path('Settings/../Reporting'),
        ])->assertSuccessful();

        // The file lands in app/Reporting, so that is what the namespace has
        // to reflect — asserting the canonical destination proves the `..`
        // was collapsed rather than carried into the namespace.
        $this->assertSame('App\\Reporting', $this->namespaceOf(app_path('Reporting/ReportSettings.php')));
    }

    public function test_it_warns_and_falls_back_when_path_is_outside_the_app_root(): void
    {
        config()->set('env-settings.class_namespace', 'App\\Settings');

        $this->artisan('env-settings:make', [
            'name' => 'OutsideSettings',
            '--path' => $this->outputPath,
        ])->expectsOutputToContain('Could not derive a namespace')
            ->assertSuccessful();

        $this->assertSame('App\\Settings', $this->namespaceOf($this->outputPath.'/OutsideSettings.php'));
    }

    public function test_explicit_namespace_option_wins_over_derivation(): void
    {
        $path = $this->appDir('Modules/Billing/Settings');

        $this->artisan('env-settings:make', [
            'name' => 'BillingSettings',
            '--path' => $path,
            '--namespace' => 'Acme\\Billing\\Config',
        ])->assertSuccessful();

        $this->assertSame('Acme\\Billing\\Config', $this->namespaceOf($path.'/BillingSettings.php'));
    }

    public function test_explicit_namespace_option_is_not_warned_about_outside_the_app_root(): void
    {
        $this->artisan('env-settings:make', [
            'name' => 'OutsideSettings',
            '--path' => $this->outputPath,
            '--namespace' => 'Acme\\Config',
        ])->doesntExpectOutputToContain('Could not derive a namespace')
            ->assertSuccessful();

        $this->assertSame('Acme\\Config', $this->namespaceOf($this->outputPath.'/OutsideSettings.php'));
    }

    private function namespaceOf(string $file): string
    {
        $this->assertFileExists($file);

        preg_match('/^namespace (.+);$/m', (string) file_get_contents($file), $matches);

        return $matches[1] ?? '';
    }
}
