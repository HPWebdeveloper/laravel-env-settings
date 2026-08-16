<?php

declare(strict_types=1);

namespace HpWebDeveloper\LaravelEnvSettings\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

use function Laravel\Prompts\error;
use function Laravel\Prompts\outro;

class MakeEnvSettingsCommand extends Command
{
    protected $signature = 'env-settings:make
        {name : The name of the settings class (e.g. AuthSettings)}
        {--properties= : Comma-separated properties with types (e.g. domain:string,timeout:int,enabled:bool)}
        {--path= : Custom directory to create the file in (default: app/Settings)}
        {--namespace= : Explicit PHP namespace for the class (default: config env-settings.class_namespace)}';

    protected $description = 'Create a new environment settings class';

    public function handle(Filesystem $files): int
    {
        $name = $this->argument('name');
        $basePath = $this->option('path') ?? app_path('Settings');
        $filePath = rtrim($basePath, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$name.'.php';

        if ($files->exists($filePath)) {
            error("Settings class already exists: {$filePath}");

            return self::FAILURE;
        }

        $properties = $this->parseProperties($this->option('properties'));
        $namespace = $this->resolveNamespace();

        $stub = $this->buildStub($namespace, $name, $properties);

        $files->ensureDirectoryExists(dirname($filePath));
        $files->put($filePath, $stub);

        outro("Settings class created: {$filePath}");

        $this->autoRegisterInConfig($namespace.'\\'.$name);

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

        return 'App\\'.trim($namespace, '\\');
    }

    /**
     * Resolve the namespace for the new settings class.
     *
     * Priority:
     *   1. `--namespace` CLI option — fully explicit, no guessing.
     *   2. `config('env-settings.class_namespace')` — project-wide default.
     *   3. `App\Settings` — safe built-in fallback.
     *
     * This replaces the old path-to-namespace derivation which was fragile
     * with non-standard PSR-4 mappings.
     */
    private function resolveNamespace(): string
    {
        return $this->option('namespace')
            ?? config('env-settings.class_namespace', 'App\\Settings');
    }

    /**
     * Append the new class to the `register` array in the published config.
     *
     * Only runs when the user has published config/env-settings.php. Silently
     * skips if the file does not exist or the class is already listed.
     */
    private function autoRegisterInConfig(string $fqcn): void
    {
        $configPath = config_path('env-settings.php');

        if (! file_exists($configPath)) {
            return;
        }

        $content = file_get_contents($configPath);

        if ($content === false || str_contains($content, $fqcn)) {
            return;
        }

        $newContent = preg_replace_callback(
            "/'register'\s*=>\s*\[([\s\S]*?)\s*\]/",
            function (array $matches) use ($fqcn): string {
                $inner = rtrim($matches[1]);

                return "'register' => [{$inner}\n        \\{$fqcn}::class,\n    ]";
            },
            $content,
            1,
            $count,
        );

        if ($count === 1 && $newContent !== null) {
            file_put_contents($configPath, $newContent);
            $this->line("  → Registered \\{$fqcn}::class in config/env-settings.php");
        }
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
