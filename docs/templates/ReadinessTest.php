<?php

declare(strict_types=1);

namespace YourVendor\YourPackage\Tests\Phase03;

use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use PHPUnit\Framework\TestCase;

/**
 * Template: ReadinessTest
 *
 * Copy this file into tests/Phase03/ReadinessTest.php of your package
 * and replace placeholders:
 *  - Namespace: YourVendor\\YourPackage -> your package namespace
 *  - Provider FQCN and publish tags: replace TODOs accordingly
 *  - Config key slug: replace "yourpkg"
 *
 * The composer constraint assertion uses a whitespace-tolerant regex
 * so it works with composer-normalize formatting ("^11.0 || ^12.0").
 */
final class ReadinessTest extends TestCase
{
    public function test_provider_registers_paths(): void
    {
        // TODO: Replace Provider::class and publish tags
        // $mapConfig = BaseServiceProvider::pathsToPublish(YourProvider::class, 'yourpkg-config');
        // $mapMigrations = BaseServiceProvider::pathsToPublish(YourProvider::class, 'yourpkg-migrations');
        // $mapViews = BaseServiceProvider::pathsToPublish(YourProvider::class, 'yourpkg-views');
        // $this->assertNotEmpty($mapConfig);
        // $this->assertIsArray($mapMigrations);
        // $this->assertIsArray($mapViews);
        $this->assertTrue(true); // remove after wiring provider and tags
    }

    public function test_vendor_publish_config_writes_file(): void
    {
        // TODO: Replace publish tag and config key slug
        // $this->artisan('vendor:publish', ['--tag' => 'yourpkg-config', '--force' => true])->run();
        // $this->assertFileExists(config_path('yourpkg.php'));
        // $cfg = require config_path('yourpkg.php');
        // $this->assertIsArray($cfg);
        $this->assertTrue(true); // remove after wiring publish tag
    }

    public function test_composer_constraints_are_sane_for_ci(): void
    {
        $root = realpath(__DIR__.'/../../..');
        $json = json_decode((string) file_get_contents($root.'/composer.json'), true);
        $this->assertIsArray($json);
        $requires = $json['require'] ?? [];
        $this->assertIsArray($requires);
        // Allow composer-normalize spacing around OR operator
        $this->assertMatchesRegularExpression('/^\^11\.0\s*\|\|\s*\^12\.0$/', (string) ($requires['illuminate/support'] ?? ''));
    }
}
