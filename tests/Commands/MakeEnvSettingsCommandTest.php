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
     * Write a published config that imports classes and registers them short.
     *
     * @param  list<string>  $imports  each written as `use {$import};`
     */
    private function publishConfigWithImports(array $imports, string ...$registerLines): string
    {
        $path = config_path('env-settings.php');
        $use = implode("\n", array_map(static fn (string $import): string => "use {$import};", $imports));
        $body = $registerLines === [] ? '' : "\n        ".implode("\n        ", $registerLines);

        file_put_contents($path, "<?php\n\n{$use}\n\nreturn [\n\n    'register' => [{$body}\n    ],\n\n];\n");

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

    public function test_generated_class_declares_a_consistent_constructor(): void
    {
        $this->artisanCommand('env-settings:make', [
            'name' => 'CacheSettings',
            '--path' => $this->outputPath,
        ])->assertSuccessful();

        $content = $this->readFile($this->outputPath.'/CacheSettings.php');

        // Overrides extend the class, so it cannot be final: the tag in its
        // docblock is what lets PHPStan accept the factories' `new static()`.
        $this->assertMatchesRegularExpression(
            '~/\*\*(?:(?!\*/).)*@phpstan-consistent-constructor(?:(?!\*/).)*\*/\s*class CacheSettings extends EnvironmentSettings~s',
            $content,
        );
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

    public function test_it_does_not_duplicate_a_short_name_backed_by_an_import(): void
    {
        // The style the demo application uses. Compared as written, the short
        // entry never matched the fully-qualified name, so the class was
        // appended a second time.
        $configPath = $this->publishConfigWithImports(['App\Settings\AuthSettings'], 'AuthSettings::class,');

        $this->artisanCommand('env-settings:make', [
            'name' => 'AuthSettings',
            '--path' => $this->outputPath,
            '--namespace' => 'App\\Settings',
        ])->expectsOutputToContain('already registered')
            ->assertSuccessful();

        $this->assertSame(['App\\Settings\\AuthSettings'], $this->requireConfig($configPath)['register']);
    }

    public function test_it_does_not_duplicate_a_class_registered_under_an_alias(): void
    {
        $configPath = $this->publishConfigWithImports(['App\Settings\AuthSettings as Auth'], 'Auth::class,');

        $this->artisanCommand('env-settings:make', [
            'name' => 'AuthSettings',
            '--path' => $this->outputPath,
            '--namespace' => 'App\\Settings',
        ])->expectsOutputToContain('already registered')
            ->assertSuccessful();

        $this->assertSame(['App\\Settings\\AuthSettings'], $this->requireConfig($configPath)['register']);
    }

    public function test_it_does_not_duplicate_a_class_from_a_group_import(): void
    {
        $configPath = $this->publishConfigWithImports(
            ['App\Settings\{AiSettings, AuthSettings}'],
            'AiSettings::class,',
            'AuthSettings::class,',
        );

        $this->artisanCommand('env-settings:make', [
            'name' => 'AuthSettings',
            '--path' => $this->outputPath,
            '--namespace' => 'App\\Settings',
        ])->expectsOutputToContain('already registered')
            ->assertSuccessful();

        $this->assertSame(
            ['App\\Settings\\AiSettings', 'App\\Settings\\AuthSettings'],
            $this->requireConfig($configPath)['register']
        );
    }

    public function test_it_does_not_duplicate_a_class_written_in_another_case(): void
    {
        // PHP class names are case-insensitive, so this is the same class.
        $configPath = $this->publishConfig('\app\settings\authsettings::class,');

        $this->artisanCommand('env-settings:make', [
            'name' => 'AuthSettings',
            '--path' => $this->outputPath,
            '--namespace' => 'App\\Settings',
        ])->expectsOutputToContain('already registered')
            ->assertSuccessful();

        $this->assertCount(1, $this->requireConfig($configPath)['register']);
    }

    public function test_it_registers_a_short_name_that_has_no_matching_import(): void
    {
        // Without an import, AuthSettings::class is the global \AuthSettings —
        // a different class — so the settings class must still be appended.
        $configPath = $this->publishConfig('AuthSettings::class,');

        $this->artisanCommand('env-settings:make', [
            'name' => 'AuthSettings',
            '--path' => $this->outputPath,
            '--namespace' => 'App\\Settings',
        ])->expectsOutputToContain('Registered')
            ->assertSuccessful();

        $this->assertSame(['AuthSettings', 'App\\Settings\\AuthSettings'], $this->requireConfig($configPath)['register']);
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

    // -------------------------------------------------------------------
    // --shared
    // -------------------------------------------------------------------

    public function test_shared_gives_the_property_a_default_and_drops_it_from_the_factories(): void
    {
        $this->artisanCommand('env-settings:make', [
            'name' => 'AiSettings',
            '--properties' => 'provider:string,currency:string',
            '--shared' => 'currency=EUR',
            '--path' => $this->outputPath,
        ])->assertSuccessful();

        $content = $this->readFile($this->outputPath.'/AiSettings.php');

        $this->assertStringContainsString("public string \$currency = 'EUR',", $content);
        // The whole point: written once in the signature, not in each factory.
        $this->assertStringNotContainsString('currency:', $content);
        $this->assertStringContainsString('provider:', $content);
    }

    public function test_shared_renders_each_scalar_type_as_a_php_literal(): void
    {
        $this->artisanCommand('env-settings:make', [
            'name' => 'TypedSettings',
            '--properties' => 'name:string,retries:int,ratio:float,rate:float,verbose:bool,strict:bool',
            '--shared' => 'name=eu,retries=3,ratio=0.2,rate=2,verbose=false,strict=true',
            '--path' => $this->outputPath,
        ])->assertSuccessful();

        $content = $this->readFile($this->outputPath.'/TypedSettings.php');

        $this->assertStringContainsString("public string \$name = 'eu',", $content);
        $this->assertStringContainsString('public int $retries = 3,', $content);
        $this->assertStringContainsString('public float $ratio = 0.2,', $content);
        // A float given as a whole number still has to render as a float literal.
        $this->assertStringContainsString('public float $rate = 2.0,', $content);
        $this->assertStringContainsString('public bool $verbose = false,', $content);
        $this->assertStringContainsString('public bool $strict = true,', $content);
    }

    public function test_shared_properties_are_ordered_after_the_required_ones(): void
    {
        // PHP requires optional parameters last, so a shared property declared
        // first in --properties still has to move.
        $this->artisanCommand('env-settings:make', [
            'name' => 'OrderedSettings',
            '--properties' => 'currency:string,provider:string',
            '--shared' => 'currency=EUR',
            '--path' => $this->outputPath,
        ])->assertSuccessful();

        $content = $this->readFile($this->outputPath.'/OrderedSettings.php');

        $this->assertLessThan(
            strpos($content, 'currency'),
            strpos($content, 'public string $provider,'),
            'the property without a default must come first or the class will not parse',
        );
    }

    public function test_a_generated_shared_class_parses_as_valid_php(): void
    {
        $this->artisanCommand('env-settings:make', [
            'name' => 'SharedLintSettings',
            '--properties' => 'currency:string,provider:string,retries:int',
            '--shared' => 'currency=EUR,retries=3',
            '--path' => $this->outputPath,
        ])->assertSuccessful();

        $lint = shell_exec('php -l '.escapeshellarg($this->outputPath.'/SharedLintSettings.php').' 2>&1');

        $this->assertStringContainsString('No syntax errors', (string) $lint);
    }

    public function test_shared_rejects_an_unknown_property_name(): void
    {
        $this->artisanCommand('env-settings:make', [
            'name' => 'UnknownSharedSettings',
            '--properties' => 'currency:string',
            '--shared' => 'curency=EUR',
            '--path' => $this->outputPath,
        ])->expectsOutputToContain('Unknown property in --shared')
            ->assertFailed();

        $this->assertFileDoesNotExist($this->outputPath.'/UnknownSharedSettings.php');
    }

    public function test_shared_rejects_a_pair_without_a_value(): void
    {
        $this->artisanCommand('env-settings:make', [
            'name' => 'NoValueSettings',
            '--properties' => 'currency:string',
            '--shared' => 'currency',
            '--path' => $this->outputPath,
        ])->expectsOutputToContain('name=value')
            ->assertFailed();
    }

    public function test_shared_rejects_a_value_that_does_not_fit_the_type(): void
    {
        // Writing `public int $retries = many,` would not compile, so this must
        // fail at the command rather than produce a broken class.
        $this->artisanCommand('env-settings:make', [
            'name' => 'BadTypeSettings',
            '--properties' => 'retries:int',
            '--shared' => 'retries=many',
            '--path' => $this->outputPath,
        ])->expectsOutputToContain('cannot be written on the command line')
            ->assertFailed();

        $this->assertFileDoesNotExist($this->outputPath.'/BadTypeSettings.php');
    }

    public function test_shared_writes_a_float_in_exponent_form_as_valid_php(): void
    {
        // 1e3 is a float literal as written. Appending `.0`, as a whole number
        // needs, would produce 1e3.0 — which does not parse.
        $this->artisanCommand('env-settings:make', [
            'name' => 'ExponentSettings',
            '--properties' => 'provider:string,rate:float',
            '--shared' => 'rate=1e3',
            '--path' => $this->outputPath,
        ])->assertSuccessful();

        $file = $this->outputPath.'/ExponentSettings.php';
        $lint = shell_exec('php -l '.escapeshellarg($file).' 2>&1');

        $this->assertStringContainsString('public float $rate = 1e3,', $this->readFile($file));
        $this->assertStringContainsString('No syntax errors', (string) $lint);
    }

    public function test_shared_refuses_an_int_with_a_leading_zero(): void
    {
        // PHP reads 010 as octal 8 and cannot parse 08 at all, so writing either
        // as typed would silently change the value or break the class.
        $this->artisanCommand('env-settings:make', [
            'name' => 'OctalSettings',
            '--properties' => 'timeout:int',
            '--shared' => 'timeout=010',
            '--path' => $this->outputPath,
        ])->expectsOutputToContain('leading zero')
            ->assertFailed();

        $this->assertFileDoesNotExist($this->outputPath.'/OctalSettings.php');
    }

    public function test_shared_refuses_an_int_too_large_for_php(): void
    {
        // Past PHP_INT_MAX the literal becomes a float, which an int default
        // cannot hold, so the class would not load.
        $this->artisanCommand('env-settings:make', [
            'name' => 'HugeSettings',
            '--properties' => 'limit:int',
            '--shared' => 'limit=99999999999999999999',
            '--path' => $this->outputPath,
        ])->assertFailed();

        $this->assertFileDoesNotExist($this->outputPath.'/HugeSettings.php');
    }

    public function test_shared_refuses_an_array_property_rather_than_mangling_it(): void
    {
        // A comma-separated option cannot express an array literal, and guessing
        // would emit a class that does not parse.
        $this->artisanCommand('env-settings:make', [
            'name' => 'ArraySharedSettings',
            '--properties' => 'regions:array',
            '--shared' => 'regions=eu',
            '--path' => $this->outputPath,
        ])->expectsOutputToContain('Add it to the constructor by hand')
            ->assertFailed();
    }

    public function test_shared_combines_with_sensitive(): void
    {
        $this->artisanCommand('env-settings:make', [
            'name' => 'BothSettings',
            '--properties' => 'provider:string,token:string',
            '--sensitive' => 'token',
            '--shared' => 'token=fixed',
            '--path' => $this->outputPath,
        ])->assertSuccessful();

        $content = $this->readFile($this->outputPath.'/BothSettings.php');

        $this->assertStringContainsString("#[Sensitive] public string \$token = 'fixed',", $content);
    }
}
