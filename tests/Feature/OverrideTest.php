<?php

declare(strict_types=1);

namespace HpWebDeveloper\LaravelEnvSettings\Tests\Feature;

use HpWebDeveloper\LaravelEnvSettings\Tests\Fixtures\FakeAuthSettings;
use HpWebDeveloper\LaravelEnvSettings\Tests\Fixtures\FakePaymentSettings;
use HpWebDeveloper\LaravelEnvSettings\Tests\TestCase;

class OverrideTest extends TestCase
{
    private const OVERRIDE_NAMESPACE = 'HpWebDeveloper\\LaravelEnvSettings\\Tests\\Fixtures\\Overrides';

    /** @var list<string> */
    private array $tempDirs = [];

    protected function tearDown(): void
    {
        foreach ($this->tempDirs as $dir) {
            $this->deleteDirectory($dir);
        }

        $this->tempDirs = [];

        parent::tearDown();
    }

    private function enableOverride(): void
    {
        config()->set('env-settings.override', true);
        config()->set('env-settings.override_path', __DIR__.'/../Fixtures/Overrides');
        config()->set('env-settings.override_namespace', self::OVERRIDE_NAMESPACE);
    }

    // -------------------------------------------------------------------
    // Override active
    // -------------------------------------------------------------------

    public function test_resolve_uses_override_class_when_enabled(): void
    {
        app()->detectEnvironment(fn () => 'local');
        $this->enableOverride();

        $settings = FakeAuthSettings::resolve();

        $this->assertSame('my-local-override.test', $settings->domain);
        $this->assertSame('http://my-local:8080/callback', $settings->redirect_url);
        $this->assertSame(60, $settings->timeout);
    }

    public function test_override_class_still_resolves_environment_correctly(): void
    {
        app()->detectEnvironment(fn () => 'production');
        $this->enableOverride();

        // The override class only overrides development().
        // For production, it inherits from the parent, so production values used.
        $settings = FakeAuthSettings::resolve();

        $this->assertSame('auth.example.com', $settings->domain);
        $this->assertSame(10, $settings->timeout);
    }

    // -------------------------------------------------------------------
    // Override disabled
    // -------------------------------------------------------------------

    public function test_resolve_ignores_override_when_disabled(): void
    {
        app()->detectEnvironment(fn () => 'local');
        config()->set('env-settings.override', false);
        config()->set('env-settings.override_path', __DIR__.'/../Fixtures/Overrides');
        config()->set('env-settings.override_namespace', 'HpWebDeveloper\\LaravelEnvSettings\\Tests\\Fixtures\\Overrides');

        $settings = FakeAuthSettings::resolve();

        // Should use original development() values, not override
        $this->assertSame('dev.auth.example.com', $settings->domain);
        $this->assertSame(30, $settings->timeout);
    }

    // -------------------------------------------------------------------
    // Override file doesn't exist for this class
    // -------------------------------------------------------------------

    public function test_resolve_falls_back_to_normal_when_no_override_file(): void
    {
        app()->detectEnvironment(fn () => 'local');
        $this->enableOverride();

        // FakePaymentSettings has no override file in Overrides/
        $settings = FakePaymentSettings::resolve();

        $this->assertSame('test', $settings->mode);
        $this->assertSame(1, $settings->retry_attempts);
    }

    // -------------------------------------------------------------------
    // Override path doesn't exist
    // -------------------------------------------------------------------

    public function test_resolve_falls_back_when_override_path_missing(): void
    {
        app()->detectEnvironment(fn () => 'local');
        config()->set('env-settings.override', true);
        config()->set('env-settings.override_path', __DIR__.'/../Fixtures/NonExistentFolder');
        config()->set('env-settings.override_namespace', 'HpWebDeveloper\\LaravelEnvSettings\\Tests\\Fixtures\\NonExistent');

        $settings = FakeAuthSettings::resolve();

        $this->assertSame('dev.auth.example.com', $settings->domain);
    }

    // -------------------------------------------------------------------
    // Override namespace not configured
    // -------------------------------------------------------------------

    public function test_resolve_falls_back_when_override_namespace_is_null(): void
    {
        app()->detectEnvironment(fn () => 'local');
        config()->set('env-settings.override', true);
        config()->set('env-settings.override_path', __DIR__.'/../Fixtures/Overrides');
        config()->set('env-settings.override_namespace', null);

        $settings = FakeAuthSettings::resolve();

        $this->assertSame('dev.auth.example.com', $settings->domain);
    }

    // -------------------------------------------------------------------
    // override_path resolution
    // -------------------------------------------------------------------

    public function test_relative_override_path_is_resolved_against_app_path(): void
    {
        // app_path() now points at tests/Fixtures, so the relative value
        // 'Overrides' must resolve to tests/Fixtures/Overrides.
        $this->application()->useAppPath((string) realpath(__DIR__.'/../Fixtures'));

        app()->detectEnvironment(fn () => 'local');
        config()->set('env-settings.override', true);
        config()->set('env-settings.override_path', 'Overrides');
        config()->set('env-settings.override_namespace', self::OVERRIDE_NAMESPACE);

        $settings = FakeAuthSettings::resolve();

        $this->assertSame('my-local-override.test', $settings->domain);
    }

    public function test_null_override_path_uses_the_app_settings_overrides_convention(): void
    {
        // A null override_path must resolve to app_path('Settings/Overrides')
        // at runtime rather than being read from the config cache.
        $appPath = $this->makeTempAppPath('Settings/Overrides', 'FakeAuthSettings.php');
        $this->application()->useAppPath($appPath);

        app()->detectEnvironment(fn () => 'local');
        config()->set('env-settings.override', true);
        config()->set('env-settings.override_path', null);
        config()->set('env-settings.override_namespace', self::OVERRIDE_NAMESPACE);

        $settings = FakeAuthSettings::resolve();

        $this->assertSame('my-local-override.test', $settings->domain);
    }

    public function test_empty_override_path_uses_the_app_settings_overrides_convention(): void
    {
        $appPath = $this->makeTempAppPath('Settings/Overrides', 'FakeAuthSettings.php');
        $this->application()->useAppPath($appPath);

        app()->detectEnvironment(fn () => 'local');
        config()->set('env-settings.override', true);
        config()->set('env-settings.override_path', '');
        config()->set('env-settings.override_namespace', self::OVERRIDE_NAMESPACE);

        $settings = FakeAuthSettings::resolve();

        $this->assertSame('my-local-override.test', $settings->domain);
    }

    public function test_absolute_override_path_is_used_as_is(): void
    {
        // app_path() points somewhere with no overrides at all; the absolute
        // path must win rather than being appended to it.
        $this->application()->useAppPath($this->makeTempAppPath());

        app()->detectEnvironment(fn () => 'local');
        config()->set('env-settings.override', true);
        config()->set('env-settings.override_path', realpath(__DIR__.'/../Fixtures/Overrides'));
        config()->set('env-settings.override_namespace', self::OVERRIDE_NAMESPACE);

        $settings = FakeAuthSettings::resolve();

        $this->assertSame('my-local-override.test', $settings->domain);
    }

    public function test_resolve_falls_back_when_override_path_is_not_a_string(): void
    {
        app()->detectEnvironment(fn () => 'local');
        config()->set('env-settings.override', true);
        config()->set('env-settings.override_path', ['not', 'a', 'path']);
        config()->set('env-settings.override_namespace', self::OVERRIDE_NAMESPACE);

        $settings = FakeAuthSettings::resolve();

        $this->assertSame('dev.auth.example.com', $settings->domain);
    }

    // -------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------

    /**
     * Create a throwaway app path, optionally seeding a marker file inside it.
     *
     * The resolver only checks that the file exists — the override class itself
     * is loaded from `override_namespace` via the autoloader — so an empty
     * marker is enough to stand in for a published override.
     */
    private function makeTempAppPath(?string $subDirectory = null, ?string $markerFile = null): string
    {
        $appPath = sys_get_temp_dir().DIRECTORY_SEPARATOR.'env-settings-test-'.uniqid();
        $target = $subDirectory === null
            ? $appPath
            : $appPath.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $subDirectory);

        mkdir($target, 0o777, true);

        if ($markerFile !== null) {
            touch($target.DIRECTORY_SEPARATOR.$markerFile);
        }

        $this->tempDirs[] = $appPath;

        return $appPath;
    }

    private function deleteDirectory(string $directory): void
    {
        if (! is_dir($directory)) {
            return;
        }

        foreach (scandir($directory) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $path = $directory.DIRECTORY_SEPARATOR.$entry;

            is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
        }

        rmdir($directory);
    }
}
