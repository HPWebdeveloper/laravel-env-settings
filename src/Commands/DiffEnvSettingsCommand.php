<?php

declare(strict_types=1);

namespace HpWebDeveloper\LaravelEnvSettings\Commands;

use HpWebDeveloper\LaravelEnvSettings\Commands\Concerns\InteractsWithConsoleInput;
use HpWebDeveloper\LaravelEnvSettings\Commands\Concerns\MasksSensitiveValues;
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
    use InteractsWithConsoleInput;
    use MasksSensitiveValues;

    protected $signature = 'env-settings:diff
        {class? : Fully qualified class name of the settings class}
        {env1? : First environment method name (e.g. development)}
        {env2? : Second environment method name (e.g. production)}';

    protected $description = 'Compare settings between two environments';

    public function handle(): int
    {
        $classArg = $this->stringArgument('class');
        $env1Arg = $this->stringArgument('env1');
        $env2Arg = $this->stringArgument('env2');

        $settingsClass = $classArg === null ? null : $this->toSettingsClass($classArg);

        // If class looks invalid but env1/env2 were also not provided, fall back
        // to the interactive flow rather than hard-failing.
        if ($classArg !== null && $settingsClass === null) {
            if ($env1Arg === null && $env2Arg === null) {
                warning("'{$classArg}' is not a valid EnvironmentSettings subclass — falling back to interactive selection.");
            } else {
                error("Class {$classArg} is not a valid EnvironmentSettings subclass.");

                return self::FAILURE;
            }
        }

        $class = $settingsClass ?? $this->promptForClass();

        if ($class === null) {
            warning('No settings classes registered in config(\'env-settings.register\').');
            note('Usage: php artisan env-settings:diff "App\\Settings\\AuthSettings" development production');

            return self::SUCCESS;
        }

        $environments = $this->detectEnvironments($class);
        $env1 = $env1Arg ?? $this->selectFrom(
            'First environment',
            $environments,
            'Select the base environment for the comparison.',
        );
        $env2 = $env2Arg ?? $this->selectFrom(
            'Second environment',
            array_values(array_diff($environments, [$env1])),
            'Select the environment to compare against.',
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
            $raw1 = $prop->getValue($instance1);
            $raw2 = $prop->getValue($instance2);
            $val1 = $this->formatValue($raw1);
            $val2 = $this->formatValue($raw2);

            // Compare the real values, then mask for display, so a sensitive
            // property is still reported as differing without being revealed.
            $diff = $val1 !== $val2 ? ' *' : '';

            $val1 = $this->maskIfSensitive($prop, $raw1, $val1);
            $val2 = $this->maskIfSensitive($prop, $raw2, $val2);

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

    /**
     * Narrow an arbitrary string to a settings class name.
     *
     * @return class-string<EnvironmentSettings>|null
     */
    private function toSettingsClass(string $class): ?string
    {
        return class_exists($class) && is_subclass_of($class, EnvironmentSettings::class)
            ? $class
            : null;
    }

    /**
     * @return class-string<EnvironmentSettings>|null
     */
    private function promptForClass(): ?string
    {
        $registered = config('env-settings.register', []);

        if (! is_array($registered)) {
            return null;
        }

        $classes = [];

        foreach ($registered as $candidate) {
            if (! is_string($candidate)) {
                continue;
            }

            $settingsClass = $this->toSettingsClass($candidate);

            if ($settingsClass !== null) {
                $classes[] = $settingsClass;
            }
        }

        if ($classes === []) {
            return null;
        }

        $labels = [];

        foreach ($classes as $settingsClass) {
            $labels[$settingsClass] = class_basename($settingsClass).' ('.$settingsClass.')';
        }

        $selected = select(
            label: 'Which settings class would you like to compare?',
            options: $labels,
            scroll: 10,
            hint: 'Arrow keys to navigate, Enter to select.',
        );

        // `select()` returns the option key, which here is the class name.
        // Numeric-looking keys come back as int, so map through the list.
        return is_string($selected) ? $this->toSettingsClass($selected) : ($classes[$selected] ?? null);
    }

    /**
     * Prompt for one of a list of values.
     *
     * `select()` is declared as returning `int|string` because it yields the
     * option key; for a list that key is the position, so resolve it back to
     * the value rather than leaving the union to leak into callers.
     *
     * @param  list<string>  $options
     */
    private function selectFrom(string $label, array $options, string $hint): string
    {
        $selected = select(label: $label, options: $options, hint: $hint, scroll: 10);

        if (is_string($selected)) {
            return $selected;
        }

        return $options[$selected] ?? '';
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
