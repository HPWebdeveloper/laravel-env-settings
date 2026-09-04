<?php

declare(strict_types=1);

namespace HpWebDeveloper\LaravelEnvSettings\Commands;

use BackedEnum;
use HpWebDeveloper\LaravelEnvSettings\Commands\Concerns\InteractsWithConsoleInput;
use HpWebDeveloper\LaravelEnvSettings\Commands\Concerns\MasksSensitiveValues;
use HpWebDeveloper\LaravelEnvSettings\EnvironmentSettings;
use Illuminate\Console\Command;
use ReflectionClass;
use ReflectionProperty;
use UnitEnum;

use function Laravel\Prompts\note;

class ShowEnvSettingsCommand extends Command
{
    use InteractsWithConsoleInput;
    use MasksSensitiveValues;

    protected $signature = 'env-settings:show
        {class? : Fully qualified class name of a specific settings class}';

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

        $this->renderSettings($class);

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
                $this->renderSettings($class);
                $this->newLine();
            }
        }

        return self::SUCCESS;
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

        if ($value instanceof BackedEnum) {
            return (string) $value->value;
        }

        if ($value instanceof UnitEnum) {
            return $value->name;
        }

        if (is_object($value) && ! method_exists($value, '__toString')) {
            return '[object: '.get_class($value).']';
        }

        if ($value === null) {
            return 'null';
        }

        return (string) $value;
    }
}
