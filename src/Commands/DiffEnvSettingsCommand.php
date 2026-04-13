<?php

declare(strict_types=1);

namespace HpWebDeveloper\LaravelEnvSettings\Commands;

use HpWebDeveloper\LaravelEnvSettings\EnvironmentSettings;
use Illuminate\Console\Command;
use ReflectionClass;
use ReflectionProperty;

class DiffEnvSettingsCommand extends Command
{
    protected $signature = 'env-settings:diff
        {class : Fully qualified class name of the settings class}
        {env1 : First environment method name (e.g. development)}
        {env2 : Second environment method name (e.g. production)}';

    protected $description = 'Compare settings between two environments';

    public function handle(): int
    {
        $class = $this->argument('class');
        $env1 = $this->argument('env1');
        $env2 = $this->argument('env2');

        if (! class_exists($class) || ! is_subclass_of($class, EnvironmentSettings::class)) {
            $this->error("Class {$class} is not a valid EnvironmentSettings subclass.");

            return self::FAILURE;
        }

        if (! method_exists($class, $env1)) {
            $this->error("Method {$class}::{$env1}() does not exist.");

            return self::FAILURE;
        }

        if (! method_exists($class, $env2)) {
            $this->error("Method {$class}::{$env2}() does not exist.");

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
