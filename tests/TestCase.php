<?php

declare(strict_types=1);

namespace HpWebDeveloper\LaravelEnvSettings\Tests;

use HpWebDeveloper\LaravelEnvSettings\EnvSettingsServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        return [
            EnvSettingsServiceProvider::class,
        ];
    }
}
