<?php

declare(strict_types=1);

namespace Vendor\Package\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Vendor\Package\PackageServiceProvider;

abstract class TestCase extends Orchestra
{
    /**
     * Compatibility shim for older/newer Testbench/Laravel versions that
     * expect this static property to exist on the concrete test case.
     * Some Testbench versions reset static::$latestResponse in tearDown().
     *
     * @var mixed
     */
    public static $latestResponse;

    protected function getPackageProviders($app)
    {
        return [
            PackageServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        // Configure SQLite in-memory by default
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => env('DB_DATABASE', ':memory:'),
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
    }
}
