<?php

declare(strict_types=1);

namespace HpWebDeveloper\LaravelEnvSettings\Tests;

use HpWebDeveloper\LaravelEnvSettings\EnvSettingsServiceProvider;
use Illuminate\Foundation\Application;
use Illuminate\Testing\PendingCommand;
use Orchestra\Testbench\TestCase as Orchestra;
use RuntimeException;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        return [
            EnvSettingsServiceProvider::class,
        ];
    }

    /**
     * The booted application.
     *
     * `$app` is declared nullable because it only exists between setUp and
     * tearDown. Inside a test it is always present, so narrow it here rather
     * than at every call site.
     */
    protected function application(): Application
    {
        $app = $this->app;

        if ($app === null) {
            throw new RuntimeException('The application has not been booted.');
        }

        return $app;
    }

    /**
     * Run an Artisan command and return the pending command to assert against.
     *
     * `artisan()` is declared as returning `PendingCommand|int` because it
     * returns the exit code when console output is not being mocked. Tests
     * always mock it, so narrow the union here instead of at every call site.
     *
     * @param  array<string, mixed>  $parameters
     */
    protected function artisanCommand(string $command, array $parameters = []): PendingCommand
    {
        $pending = $this->artisan($command, $parameters);

        if (! $pending instanceof PendingCommand) {
            throw new RuntimeException(
                "Console output is not being mocked, so [{$command}] cannot be asserted against."
            );
        }

        return $pending;
    }

    /**
     * Read a file that the test expects to exist.
     *
     * Fails the test with a useful message rather than letting `false` flow
     * into an assertion as an empty haystack.
     */
    protected function readFile(string $path): string
    {
        $contents = file_get_contents($path);

        $this->assertNotFalse($contents, "Unable to read {$path}.");

        return $contents;
    }
}
