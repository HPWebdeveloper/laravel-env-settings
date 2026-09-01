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

        @unlink(config_path('env-settings.php'));

        parent::tearDown();
    }

    /**
     * Write a published config whose `register` array holds the given lines.
     */
    private function publishConfig(string ...$registerLines): string
    {
        $path = config_path('env-settings.php');
        $body = $registerLines === [] ? '' : "\n        ".implode("\n        ", $registerLines);

        file_put_contents($path, "<?php\n\nreturn [\n\n    'register' => [{$body}\n    ],\n\n];\n");

        return $path;
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
        $this->artisanCommand('env-settings:make', [
            'name' => 'AuthSettings',
            '--path' => $this->outputPath,
        ])->assertSuccessful();

        $this->assertFileExists($this->outputPath.'/AuthSettings.php');
    }

    public function test_generated_file_contains_correct_class_name(): void
    {
        $this->artisanCommand('env-settings:make', [
            'name' => 'PaymentSettings',
            '--path' => $this->outputPath,
        ])->assertSuccessful();

        $content = $this->readFile($this->outputPath.'/PaymentSettings.php');

        $this->assertStringContainsString('class PaymentSettings extends EnvironmentSettings', $content);
    }

    public function test_generated_file_has_development_and_production_methods(): void
    {
        $this->artisanCommand('env-settings:make', [
            'name' => 'QueueSettings',
            '--path' => $this->outputPath,
        ])->assertSuccessful();

        $content = $this->readFile($this->outputPath.'/QueueSettings.php');

        $this->assertStringContainsString('public static function development(): static', $content);
        $this->assertStringContainsString('public static function production(): static', $content);
    }

    public function test_generated_file_has_default_example_property_when_no_properties_given(): void
    {
        $this->artisanCommand('env-settings:make', [
            'name' => 'BasicSettings',
            '--path' => $this->outputPath,
        ])->assertSuccessful();

        $content = $this->readFile($this->outputPath.'/BasicSettings.php');

        $this->assertStringContainsString('public string $example', $content);
    }

    public function test_properties_option_generates_typed_constructor(): void
    {
        $this->artisanCommand('env-settings:make', [
            'name' => 'ApiSettings',
            '--properties' => 'domain:string,timeout:int,enabled:bool',
            '--path' => $this->outputPath,
        ])->assertSuccessful();

        $content = $this->readFile($this->outputPath.'/ApiSettings.php');

        $this->assertStringContainsString('public string $domain', $content);
        $this->assertStringContainsString('public int $timeout', $content);
        $this->assertStringContainsString('public bool $enabled', $content);
    }

    public function test_properties_option_generates_correct_defaults_in_factory_methods(): void
    {
        $this->artisanCommand('env-settings:make', [
            'name' => 'MixedSettings',
            '--properties' => 'name:string,count:int,rate:float,active:bool,tags:array',
            '--path' => $this->outputPath,
        ])->assertSuccessful();

        $content = $this->readFile($this->outputPath.'/MixedSettings.php');

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

        $this->artisanCommand('env-settings:make', [
            'name' => 'ExistingSettings',
            '--path' => $this->outputPath,
        ])->assertFailed();
    }

    public function test_generated_file_has_correct_use_statement(): void
    {
        $this->artisanCommand('env-settings:make', [
            'name' => 'TestSettings',
            '--path' => $this->outputPath,
        ])->assertSuccessful();

        $content = $this->readFile($this->outputPath.'/TestSettings.php');

        $this->assertStringContainsString('use HpWebDeveloper\LaravelEnvSettings\EnvironmentSettings;', $content);
    }

    // -------------------------------------------------------------------
    // --sensitive
    // -------------------------------------------------------------------

    public function test_sensitive_option_marks_the_named_properties(): void
    {
        $this->artisanCommand('env-settings:make', [
            'name' => 'VaultSettings',
            '--properties' => 'endpoint:string,passphrase:string,timeout:int',
            '--sensitive' => 'passphrase',
            '--path' => $this->outputPath,
        ])->assertSuccessful();

        $content = $this->readFile($this->outputPath.'/VaultSettings.php');

        $this->assertStringContainsString('#[Sensitive] public string $passphrase,', $content);
        $this->assertStringContainsString('use HpWebDeveloper\LaravelEnvSettings\Attributes\Sensitive;', $content);
        // Only the named property is marked.
        $this->assertStringContainsString("\n        public string \$endpoint,", $content);
        $this->assertStringContainsString("\n        public int \$timeout,", $content);
    }

    public function test_sensitive_import_is_absent_when_the_option_is_not_used(): void
    {
        $this->artisanCommand('env-settings:make', [
            'name' => 'PlainSettings',
            '--properties' => 'endpoint:string',
            '--path' => $this->outputPath,
        ])->assertSuccessful();

        $content = $this->readFile($this->outputPath.'/PlainSettings.php');

        $this->assertStringNotContainsString('Sensitive', $content);
    }

    public function test_sensitive_option_rejects_unknown_property_names(): void
    {
        // A typo silently marking nothing would leave the value unmasked while
        // the developer believes otherwise, so the command fails instead.
        $this->artisanCommand('env-settings:make', [
            'name' => 'TypoSettings',
            '--properties' => 'passphrase:string',
            '--sensitive' => 'pasphrase',
            '--path' => $this->outputPath,
        ])->expectsOutputToContain('Unknown property in --sensitive')
            ->assertFailed();

        $this->assertFileDoesNotExist($this->outputPath.'/TypoSettings.php');
    }

    public function test_a_generated_sensitive_class_parses_as_valid_php(): void
    {
        $this->artisanCommand('env-settings:make', [
            'name' => 'LintSettings',
            '--properties' => 'token:string,api_key:string',
            '--sensitive' => 'token,api_key',
            '--path' => $this->outputPath,
        ])->assertSuccessful();

        $lint = shell_exec('php -l '.escapeshellarg($this->outputPath.'/LintSettings.php').' 2>&1');

        $this->assertStringContainsString('No syntax errors', (string) $lint);
    }

    // -------------------------------------------------------------------
    // Namespace resolution
    // -------------------------------------------------------------------

    public function test_it_uses_the_configured_namespace_when_no_path_is_given(): void
    {
        config()->set('env-settings.class_namespace', 'App\\Settings');

        $path = $this->appDir('Settings');

        $this->artisanCommand('env-settings:make', ['name' => 'DefaultSettings'])->assertSuccessful();

        $this->assertSame('App\\Settings', $this->namespaceOf($path.'/DefaultSettings.php'));
    }

    public function test_it_derives_the_namespace_from_a_path_inside_the_app_root(): void
    {
        // A modular layout: the class has to be namespaced to match where it
        // lands, otherwise PSR-4 can never autoload it.
        $path = $this->appDir('Modules/Billing/Settings');

        $this->artisanCommand('env-settings:make', [
            'name' => 'BillingSettings',
            '--path' => $path,
        ])->assertSuccessful();

        $this->assertSame('App\\Modules\\Billing\\Settings', $this->namespaceOf($path.'/BillingSettings.php'));
    }

    public function test_derived_namespace_collapses_relative_path_segments(): void
    {
        $this->appDirs[] = app_path('Reporting');
        $this->appDirs[] = app_path('Settings');

        $this->artisanCommand('env-settings:make', [
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

        $this->artisanCommand('env-settings:make', [
            'name' => 'OutsideSettings',
            '--path' => $this->outputPath,
        ])->expectsOutputToContain('Could not derive a namespace')
            ->assertSuccessful();

        $this->assertSame('App\\Settings', $this->namespaceOf($this->outputPath.'/OutsideSettings.php'));
    }

    public function test_explicit_namespace_option_wins_over_derivation(): void
    {
        $path = $this->appDir('Modules/Billing/Settings');

        $this->artisanCommand('env-settings:make', [
            'name' => 'BillingSettings',
            '--path' => $path,
            '--namespace' => 'Acme\\Billing\\Config',
        ])->assertSuccessful();

        $this->assertSame('Acme\\Billing\\Config', $this->namespaceOf($path.'/BillingSettings.php'));
    }

    public function test_explicit_namespace_option_is_not_warned_about_outside_the_app_root(): void
    {
        $this->artisanCommand('env-settings:make', [
            'name' => 'OutsideSettings',
            '--path' => $this->outputPath,
            '--namespace' => 'Acme\\Config',
        ])->doesntExpectOutputToContain('Could not derive a namespace')
            ->assertSuccessful();

        $this->assertSame('Acme\\Config', $this->namespaceOf($this->outputPath.'/OutsideSettings.php'));
    }

    public function test_it_fails_on_an_invalid_explicit_namespace(): void
    {
        $this->artisanCommand('env-settings:make', [
            'name' => 'BadSettings',
            '--path' => $this->outputPath,
            '--namespace' => '123-Not Valid',
        ])->expectsOutputToContain('Not a valid PHP namespace')
            ->assertFailed();

        // Nothing is written: an invalid namespace would not even parse.
        $this->assertFileDoesNotExist($this->outputPath.'/BadSettings.php');
    }

    public function test_it_ignores_an_invalid_configured_namespace(): void
    {
        config()->set('env-settings.class_namespace', '123 Nope');

        $this->artisanCommand('env-settings:make', [
            'name' => 'FallbackSettings',
            '--path' => $this->outputPath,
        ])->assertSuccessful();

        $this->assertSame('App\\Settings', $this->namespaceOf($this->outputPath.'/FallbackSettings.php'));
    }

    // -------------------------------------------------------------------
    // Auto-registration in the published config
    // -------------------------------------------------------------------

    public function test_it_registers_a_class_whose_name_matches_a_commented_out_example(): void
    {
        // The published config ships these examples commented out. They must
        // not be mistaken for real entries, or the class silently never
        // registers and stays inert.
        $configPath = $this->publishConfig(
            '// \App\Settings\AuthSettings::class,',
            '// \App\Settings\PaymentSettings::class,',
        );

        $this->artisanCommand('env-settings:make', [
            'name' => 'AuthSettings',
            '--path' => $this->outputPath,
            '--namespace' => 'App\\Settings',
        ])->assertSuccessful();

        $this->assertStringContainsString(
            '\App\Settings\AuthSettings::class,',
            $this->uncommentedLines($configPath)
        );
    }

    public function test_it_does_not_duplicate_a_class_that_is_already_registered(): void
    {
        $configPath = $this->publishConfig('\App\Settings\AuthSettings::class,');

        $this->artisanCommand('env-settings:make', [
            'name' => 'AuthSettings',
            '--path' => $this->outputPath,
            '--namespace' => 'App\\Settings',
        ])->expectsOutputToContain('already registered')
            ->assertSuccessful();

        $this->assertSame(
            1,
            substr_count($this->readFile($configPath), 'AuthSettings::class')
        );
    }

    public function test_it_appends_to_an_empty_register_array(): void
    {
        $configPath = $this->publishConfig();

        $this->artisanCommand('env-settings:make', [
            'name' => 'ReportingSettings',
            '--path' => $this->outputPath,
            '--namespace' => 'App\\Settings',
        ])->expectsOutputToContain('Registered')
            ->assertSuccessful();

        $this->assertStringContainsString(
            '\App\Settings\ReportingSettings::class,',
            $this->uncommentedLines($configPath)
        );
    }

    public function test_it_warns_when_the_config_has_not_been_published(): void
    {
        $this->assertFileDoesNotExist(config_path('env-settings.php'));

        $this->artisanCommand('env-settings:make', [
            'name' => 'UnpublishedSettings',
            '--path' => $this->outputPath,
        ])->expectsOutputToContain('not published')
            ->assertSuccessful();
    }

    public function test_it_warns_when_no_register_array_is_present(): void
    {
        file_put_contents(config_path('env-settings.php'), "<?php\n\nreturn [\n    'override' => false,\n];\n");

        $this->artisanCommand('env-settings:make', [
            'name' => 'OrphanSettings',
            '--path' => $this->outputPath,
        ])->expectsOutputToContain('Could not find a `register` array')
            ->assertSuccessful();
    }

    public function test_it_does_not_corrupt_a_config_whose_register_array_contains_a_bracket(): void
    {
        // A `]` inside a comment or string must not be mistaken for the end
        // of the array — truncating there writes back unparseable PHP.
        $configPath = $this->publishConfig(
            '// use [] to disable every setting',
            '\App\Settings\ExistingSettings::class,',
            "// see docs['register'] for details",
        );

        $this->artisanCommand('env-settings:make', [
            'name' => 'AddedSettings',
            '--path' => $this->outputPath,
            '--namespace' => 'App\\Settings',
        ])->assertSuccessful();

        $register = $this->requireConfig($configPath)['register'];

        $this->assertSame(
            ['App\\Settings\\ExistingSettings', 'App\\Settings\\AddedSettings'],
            $register
        );
    }

    public function test_it_finds_existing_entries_beyond_a_bracket_in_a_comment(): void
    {
        // The entry sits after the bracket, so a truncating parser would miss
        // it and append a duplicate.
        $configPath = $this->publishConfig(
            '// arrays look like []',
            '\App\Settings\AuthSettings::class,',
        );

        $this->artisanCommand('env-settings:make', [
            'name' => 'AuthSettings',
            '--path' => $this->outputPath,
            '--namespace' => 'App\\Settings',
        ])->expectsOutputToContain('already registered')
            ->assertSuccessful();

        $this->assertSame(['App\\Settings\\AuthSettings'], $this->requireConfig($configPath)['register']);
    }

    public function test_it_does_not_corrupt_a_config_with_a_block_comment_in_the_register_array(): void
    {
        $configPath = $this->publishConfig(
            '/* disabled: \App\Settings\OldSettings::class, [x] */',
            '\App\Settings\KeptSettings::class,',
        );

        $this->artisanCommand('env-settings:make', [
            'name' => 'NewSettings',
            '--path' => $this->outputPath,
            '--namespace' => 'App\\Settings',
        ])->assertSuccessful();

        $this->assertSame(
            ['App\\Settings\\KeptSettings', 'App\\Settings\\NewSettings'],
            $this->requireConfig($configPath)['register']
        );
    }

    /**
     * Load the written config back as PHP.
     *
     * Copied to a uniquely named file so each call really re-parses it; a
     * corrupted config surfaces here as a ParseError rather than passing a
     * string assertion.
     *
     * @return array<string, mixed>
     */
    private function requireConfig(string $configPath): array
    {
        $copy = $this->outputPath.'/loaded-config-'.uniqid().'.php';
        copy($configPath, $copy);

        return require $copy;
    }

    /**
     * The config file with every comment removed, so assertions cannot be
     * satisfied by a commented-out line.
     */
    private function uncommentedLines(string $configPath): string
    {
        $content = $this->readFile($configPath);
        $content = (string) preg_replace('%/\*[\s\S]*?\*/%', '', $content);

        return (string) preg_replace('%(//|\#).*$%m', '', $content);
    }

    private function namespaceOf(string $file): string
    {
        $this->assertFileExists($file);

        preg_match('/^namespace (.+);$/m', $this->readFile($file), $matches);

        return $matches[1] ?? '';
    }
}
