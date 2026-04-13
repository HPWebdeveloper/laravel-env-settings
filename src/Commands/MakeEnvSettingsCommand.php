<?php

declare(strict_types=1);

namespace HpWebDeveloper\LaravelEnvSettings\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class MakeEnvSettingsCommand extends Command
{
    protected $signature = 'env-settings:make
        {name : The name of the settings class (e.g. AuthSettings)}
        {--properties= : Comma-separated properties with types (e.g. domain:string,timeout:int,enabled:bool)}
        {--path= : Custom directory to create the file in (default: app/Settings)}';

    protected $description = 'Create a new environment settings class';

    public function handle(Filesystem $files): int
    {
        $name = $this->argument('name');
        $basePath = $this->option('path') ?? app_path('Settings');
        $filePath = rtrim($basePath, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$name.'.php';

        if ($files->exists($filePath)) {
            $this->error("Settings class already exists: {$filePath}");

            return self::FAILURE;
        }

        $properties = $this->parseProperties($this->option('properties'));
        $namespace = $this->deriveNamespace($basePath);

        $stub = $this->buildStub($namespace, $name, $properties);

        $files->ensureDirectoryExists(dirname($filePath));
        $files->put($filePath, $stub);

        $this->info("Settings class created: {$filePath}");

        return self::SUCCESS;
    }

    /**
     * @return array<int, array{name: string, type: string}>
     */
    private function parseProperties(?string $raw): array
    {
        if (! $raw) {
            return [
                ['name' => 'example', 'type' => 'string'],
            ];
        }

        $properties = [];

        foreach (explode(',', $raw) as $prop) {
            $parts = explode(':', trim($prop));
            $properties[] = [
                'name' => trim($parts[0]),
                'type' => isset($parts[1]) ? trim($parts[1]) : 'string',
            ];
        }

        return $properties;
    }

    private function deriveNamespace(string $path): string
    {
        $appPath = app_path();
        $relativePath = str_starts_with($path, $appPath)
            ? substr($path, strlen($appPath))
            : DIRECTORY_SEPARATOR.'Settings';

        $namespace = str_replace(DIRECTORY_SEPARATOR, '\\', $relativePath);

        return 'App'.ltrim($namespace, '\\');
    }

    /**
     * @param  array<int, array{name: string, type: string}>  $properties
     */
    private function buildStub(string $namespace, string $class, array $properties): string
    {
        $stub = file_get_contents(__DIR__.'/../../stubs/env-settings.stub');

        $propsLines = [];
        $devValues = [];
        $prodValues = [];

        foreach ($properties as $prop) {
            $propsLines[] = "        public {$prop['type']} \${$prop['name']},";
            $default = $this->defaultForType($prop['type']);
            $devValues[] = "            {$prop['name']}: {$default}, // TODO: set development value";
            $prodValues[] = "            {$prop['name']}: {$default}, // TODO: set production value";
        }

        return str_replace(
            ['{{ namespace }}', '{{ class }}', '{{ properties }}', '{{ developmentValues }}', '{{ productionValues }}'],
            [$namespace, $class, implode("\n", $propsLines), implode("\n", $devValues), implode("\n", $prodValues)],
            $stub,
        );
    }

    private function defaultForType(string $type): string
    {
        return match ($type) {
            'int' => '0',
            'float' => '0.0',
            'bool' => 'false',
            'array' => '[]',
            default => "''",
        };
    }
}
