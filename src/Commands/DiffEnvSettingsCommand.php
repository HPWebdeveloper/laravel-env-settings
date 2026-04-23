<?php

declare(strict_types=1);

namespace HpWebDeveloper\LaravelEnvSettings\Commands;

use HpWebDeveloper\LaravelEnvSettings\EnvironmentSettings;
use Illuminate\Console\Command;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;

use function Laravel\Prompts\error;
use function Laravel\Prompts\note;
use function Laravel\Prompts\select;
use function Laravel\Prompts\warning;

class DiffEnvSettingsCommand extends Command
{
    protected $signature = 'env-settings:diff
        {class? : Fully qualified class name of the settings class}
        {env1? : First environment method name (e.g. development)}
        {env2? : Second environment method name (e.g. production)}';

    protected $description = 'Compare settings between two environments';

    public function handle(): int
    {
        $classArg = $this->argument('class');
        $env1Arg = $this->argument('env1');
        $env2Arg = $this->argument('env2');

        // If class looks invalid but env1/env2 were also not provided, fall back
        // to the interactive flow rather than hard-failing.
        if ($classArg !== null && (! class_exists($classArg) || ! is_subclass_of($classArg, EnvironmentSettings::class))) {
            if ($env1Arg === null && $env2Arg === null) {
                warning("'{$classArg}' is not a valid EnvironmentSettings subclass — falling back to interactive selection.");
                $classArg = null;
            } else {
                error("Class {$classArg} is not a valid EnvironmentSettings subclass.");

                return self::FAILURE;
            }
        }

        $class = $classArg ?? $this->promptForClass();

        if ($class === null) {
            warning('No settings classes registered in config(\'env-settings.register\').');
            note('Usage: php artisan env-settings:diff "App\\Settings\\AuthSettings" development production');

            return self::SUCCESS;
        }

        $environments = $this->detectEnvironments($class);
        $env1 = $env1Arg ?? select(
            label: 'First environment',
            options: $environments,
            hint: 'Select the base environment for the comparison.',
            scroll: 10,
        );
        $env2 = $env2Arg ?? select(
            label: 'Second environment',
            options: array_values(array_diff($environments, [$env1])),
            hint: 'Select the environment to compare against.',
            scroll: 10,
        );

        if (! method_exists($class, $env1)) {
            error("Method {$class}::{$env1}() does not exist.");

            return self::FAILURE;
        }

        if (! method_exists($class, $env2)) {
            error("Method {$class}::{$env2}() does not exist.");

            return self::FAILURE;
        }

        $instance1 = $class::{$env1}();
        $instance2 = $class::{$env2}();

        $reflection = new ReflectionClass($instance1);
        $shortName = $reflection->getShortName();

        $this->info("[ {$shortName} ] — Comparing {$env1} vs {$env2}");

        $rows = [];
        $hasDiff = false;

        foreach ($reflection->getProperties(ReflectionProperty::IS_PUBLIC) as $prop) {
            $name = $prop->getName();
            $val1 = $this->formatValue($prop->getValue($instance1));
            $val2 = $this->formatValue($prop->getValue($instance2));

            $diff = $val1 !== $val2 ? ' *' : '';

            if ($diff) {
                $hasDiff = true;
            }

            $rows[] = [$name.$diff, $val1, $val2];
        }

        $this->table(['Property', $env1, $env2], $rows);

        if ($hasDiff) {
            $this->line('* = values differ between environments');
        } else {
            $this->info('No differences found.');
        }

        return self::SUCCESS;
    }

    private function promptForClass(): ?string
    {
        $classes = array_values(array_filter(
            config('env-settings.register', []),
            fn ($c) => is_string($c) && class_exists($c) && is_subclass_of($c, EnvironmentSettings::class),
        ));

        if (empty($classes)) {
            return null;
        }

        $labels = array_combine(
            $classes,
            array_map(fn ($c) => class_basename($c).' ('.$c.')', $classes),
        );

        return select(
            label: 'Which settings class would you like to compare?',
            options: $labels,
            scroll: 10,
            hint: 'Arrow keys to navigate, Enter to select.',
        );
    }

    /**
     * Return the public static environment method names defined on the class,
     * excluding framework methods (resolve, testing, etc.).
     *
     * @param  class-string<EnvironmentSettings>  $class
     * @return list<string>
     */
    private function detectEnvironments(string $class): array
    {
        $skip = ['resolve', 'staging', 'testing'];

        $reflection = new ReflectionClass($class);
        $methods = [];

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_STATIC) as $method) {
            $name = $method->getName();

            if ($method->isAbstract() || in_array($name, $skip, true) || str_starts_with($name, '__')) {
                continue;
            }

            if ($method->getDeclaringClass()->getName() === EnvironmentSettings::class) {
                continue;
            }

            $methods[] = $name;
        }

        // Always append staging/testing if the class defines them beyond the default
        foreach (['staging', 'testing'] as $extra) {
            if (method_exists($class, $extra) && ! in_array($extra, $methods, true)) {
                $methods[] = $extra;
            }
        }

        return empty($methods) ? ['development', 'production'] : $methods;
    }

    private function formatValue(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_array($value)) {
            return json_encode($value, JSON_THROW_ON_ERROR);
        }

        if ($value instanceof EnvironmentSettings) {
            return '[nested: '.get_class($value).']';
        }

        return (string) $value;
    }
}
