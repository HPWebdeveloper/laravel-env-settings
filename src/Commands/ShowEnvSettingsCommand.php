<?php

declare(strict_types=1);

namespace HpWebDeveloper\LaravelEnvSettings\Commands;

use HpWebDeveloper\LaravelEnvSettings\EnvironmentSettings;
use Illuminate\Console\Command;
use ReflectionClass;
use ReflectionProperty;

use function Laravel\Prompts\note;

class ShowEnvSettingsCommand extends Command
{
    protected $signature = 'env-settings:show
        {class? : Fully qualified class name of a specific settings class}';

    protected $description = 'Display the resolved settings for the current environment';

    public function handle(): int
    {
        $class = $this->argument('class');

        if ($class) {
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

            $displayed = $this->formatValue($name, $value);
            $rows[] = [$name, $type, $displayed];
        }

        $this->table(['Property', 'Type', 'Value'], $rows);
    }

    private function formatValue(string $name, mixed $value): string
    {
        // Mask properties that look like secrets
        $sensitivePatterns = ['key', 'secret', 'password', 'token'];

        foreach ($sensitivePatterns as $pattern) {
            if (str_contains(strtolower($name), $pattern) && is_string($value) && $value !== '') {
                return '********';
            }
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_array($value)) {
            return json_encode($value, JSON_THROW_ON_ERROR);
        }

        if ($value instanceof EnvironmentSettings) {
            return '[nested: '.get_class($value).']';
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
