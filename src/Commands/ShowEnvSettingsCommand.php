<?php

declare(strict_types=1);

namespace HpWebDeveloper\LaravelEnvSettings\Commands;

use HpWebDeveloper\LaravelEnvSettings\Commands\Concerns\DetectsEnvironments;
use HpWebDeveloper\LaravelEnvSettings\Commands\Concerns\FormatsValues;
use HpWebDeveloper\LaravelEnvSettings\Commands\Concerns\InteractsWithConsoleInput;
use HpWebDeveloper\LaravelEnvSettings\Commands\Concerns\MasksSensitiveValues;
use HpWebDeveloper\LaravelEnvSettings\EnvironmentSettings;
use Illuminate\Console\Command;
use ReflectionClass;
use ReflectionProperty;

use function Laravel\Prompts\note;

class ShowEnvSettingsCommand extends Command
{
    use DetectsEnvironments;
    use FormatsValues;
    use InteractsWithConsoleInput;
    use MasksSensitiveValues;

    protected $signature = 'env-settings:show
        {class? : Fully qualified class name of a specific settings class}
        {--all : Show every environment side by side instead of the current one}';

    protected $description = 'Display the resolved settings for the current environment';

    public function handle(): int
    {
        $class = $this->stringArgument('class');

        if ($class !== null && $class !== '') {
            return $this->showSingle($class);
        }

        return $this->showAllRegistered();
    }

    private function showSingle(string $class): int
    {
        if (! class_exists($class) || ! is_subclass_of($class, EnvironmentSettings::class)) {
            $this->error("Class {$class} is not a valid EnvironmentSettings subclass.");

            return self::FAILURE;
        }

        $this->render($class);

        return self::SUCCESS;
    }

    private function showAllRegistered(): int
    {
        $classes = config('env-settings.register', []);

        if (empty($classes)) {
            $this->warn('No settings classes registered in config(\'env-settings.register\').');
            note('Pass a class name directly: php artisan env-settings:show "App\\Settings\\AuthSettings"');

            return self::SUCCESS;
        }

        foreach ($classes as $class) {
            if (is_string($class) && class_exists($class) && is_subclass_of($class, EnvironmentSettings::class)) {
                $this->render($class);
                $this->newLine();
            }
        }

        return self::SUCCESS;
    }

    /**
     * @param  class-string<EnvironmentSettings>  $class
     */
    private function render(string $class): void
    {
        if ($this->boolOption('all')) {
            $this->renderMatrix($class);

            return;
        }

        $this->renderSettings($class);
    }

    /**
     * Print one column per environment the class declares.
     *
     * A settings class states every environment in one file — that is the whole
     * argument for having it — but `show` printed only the current one and
     * `diff` only ever two, so seeing the whole picture meant reading the
     * source. This is that picture.
     *
     * Factories are called directly rather than resolved, so the columns are the
     * environments the class itself declares.
     *
     * @param  class-string<EnvironmentSettings>  $class
     */
    private function renderMatrix(string $class): void
    {
        $instances = [];

        foreach ($this->detectEnvironments($class) as $environment) {
            if (method_exists($class, $environment)) {
                $instances[$environment] = $class::{$environment}();
            }
        }

        if ($instances === []) {
            return;
        }

        $reflection = new ReflectionClass(reset($instances));
        $this->info("[ {$reflection->getShortName()} ] — ".implode(', ', array_keys($instances)));

        $rows = [];
        $differing = 0;
        $same = [];

        foreach ($reflection->getProperties(ReflectionProperty::IS_PUBLIC) as $prop) {
            $rendered = [];
            $raw = [];

            foreach ($instances as $environment => $instance) {
                $value = $prop->getValue($instance);
                $raw[] = $this->formatValue($value);
                // Compare real values, then mask for display, so a #[Sensitive]
                // property still earns its * without being revealed.
                $rendered[] = $this->maskIfSensitive($prop, $value, end($raw));
            }

            $differs = count(array_unique($raw)) > 1;
            $differing += $differs ? 1 : 0;

            if (! $differs) {
                $same[] = $prop->getName();
            }

            $rows[] = array_merge([$prop->getName().($differs ? ' *' : '')], $rendered);
        }

        $this->table(array_merge(['Property'], array_keys($instances)), $rows);

        if ($differing === 0) {
            $this->line('No property varies by environment — these values may not need a settings class at all.');

            return;
        }

        $this->line('* = differs somewhere');

        if ($same !== []) {
            // Naming them is the actionable half: each is a value repeated in
            // every factory that PHP could hold once, in the signature.
            $this->line(sprintf(
                '%s the same in every environment: %s',
                count($same) === 1 ? '1 property is' : count($same).' properties are',
                implode(', ', $same),
            ));
            $this->line('Each can be a constructor default instead, written once — see env-settings:make --shared.');
        }
    }

    /**
     * @param  class-string<EnvironmentSettings>  $class
     */
    private function renderSettings(string $class): void
    {
        $instance = $class::resolve();
        $reflection = new ReflectionClass($instance);
        $shortName = $reflection->getShortName();

        $this->info("[ {$shortName} ] — Environment: ".app()->environment());

        $rows = [];

        foreach ($reflection->getProperties(ReflectionProperty::IS_PUBLIC) as $prop) {
            $name = $prop->getName();
            $type = $prop->hasType() ? (string) $prop->getType() : 'mixed';
            $value = $prop->getValue($instance);

            $displayed = $this->maskIfSensitive($prop, $value, $this->formatValue($value));
            $rows[] = [$name, $type, $displayed];
        }

        $this->table(['Property', 'Type', 'Value'], $rows);
    }
}
