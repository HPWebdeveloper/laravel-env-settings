<?php

declare(strict_types=1);

namespace HpWebDeveloper\LaravelEnvSettings\Commands;

use HpWebDeveloper\LaravelEnvSettings\Support\Path;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Throwable;

use function Laravel\Prompts\error;
use function Laravel\Prompts\outro;

class MakeEnvSettingsCommand extends Command
{
    protected $signature = 'env-settings:make
        {name : The name of the settings class (e.g. AuthSettings)}
        {--properties= : Comma-separated properties with types (e.g. domain:string,timeout:int,enabled:bool)}
        {--path= : Custom directory to create the file in (default: app/Settings)}
        {--namespace= : Explicit PHP namespace for the class (default: derived from --path, else config env-settings.class_namespace)}';

    protected $description = 'Create a new environment settings class';

    public function handle(Filesystem $files): int
    {
        $name = $this->argument('name');
        $path = $this->option('path');
        $basePath = $path ?? app_path('Settings');
        $filePath = rtrim($basePath, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$name.'.php';

        if ($files->exists($filePath)) {
            error("Settings class already exists: {$filePath}");

            return self::FAILURE;
        }

        $properties = $this->parseProperties($this->option('properties'));
        $namespace = $this->resolveNamespace(is_string($path) ? $path : null);

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

    /**
     * Resolve the namespace for the new settings class.
     *
     * Priority:
     *   1. `--namespace` — explicit, never second-guessed.
     *   2. Derived from `--path`, when that directory sits under the
     *      application root. Where the file is written is what decides
     *      whether it autoloads, so once the caller picks the directory the
     *      namespace has to follow it or the class is unreachable.
     *   3. `config('env-settings.class_namespace')` — project-wide default.
     *
     * Derivation reads the root namespace from the application's own PSR-4
     * mapping instead of assuming `App\`, so a renamed app root still maps
     * correctly. When `--path` points outside the application root there is
     * no mapping to read, so the default is used and the caller is warned
     * rather than left with a class that silently fails to autoload.
     */
    private function resolveNamespace(?string $path): string
    {
        $explicit = $this->option('namespace');

        if (is_string($explicit) && trim($explicit) !== '') {
            return trim($explicit, '\\ ');
        }

        $default = $this->defaultNamespace();

        if ($path === null) {
            return $default;
        }

        $derived = $this->deriveNamespaceFromPath($path);

        if ($derived !== null) {
            return $derived;
        }

        $this->components->warn(
            "Could not derive a namespace for --path={$path} because it is outside ".app_path().'. '
            ."Falling back to {$default}; pass --namespace if that is not the PSR-4 namespace for that directory."
        );

        return $default;
    }

    private function defaultNamespace(): string
    {
        $configured = config('env-settings.class_namespace', 'App\\Settings');

        return is_string($configured) && trim($configured) !== ''
            ? trim($configured, '\\ ')
            : 'App\\Settings';
    }

    /**
     * Map a target directory onto its PSR-4 namespace.
     *
     * Returns null when the directory is not under the application root, or
     * when a path segment is not a legal namespace label — in both cases the
     * mapping cannot be known from here and guessing would emit a file that
     * does not autoload.
     */
    private function deriveNamespaceFromPath(string $path): ?string
    {
        $target = Path::canonicalize(Path::isAbsolute($path) ? $path : base_path($path));
        $appPath = Path::canonicalize(app_path());

        if ($target === $appPath) {
            $relative = '';
        } elseif (str_starts_with($target, $appPath.'/')) {
            $relative = substr($target, strlen($appPath) + 1);
        } else {
            return null;
        }

        $rootNamespace = $this->rootNamespace();

        if ($rootNamespace === null) {
            return null;
        }

        if ($relative === '') {
            return $rootNamespace;
        }

        $segments = explode('/', $relative);

        foreach ($segments as $segment) {
            if (preg_match('/^[A-Za-z_\x80-\xff][A-Za-z0-9_\x80-\xff]*$/', $segment) !== 1) {
                return null;
            }
        }

        return $rootNamespace.'\\'.implode('\\', $segments);
    }

    /**
     * The application's root namespace, read from its PSR-4 autoload mapping.
     */
    private function rootNamespace(): ?string
    {
        try {
            return trim($this->laravel->getNamespace(), '\\');
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Append the new class to the `register` array in the published config.
     *
     * A settings class is inert until it is listed there, so anything that
     * stops the append from happening is reported rather than swallowed.
     */
    private function autoRegisterInConfig(string $fqcn): void
    {
        $configPath = config_path('env-settings.php');

        if (! file_exists($configPath)) {
            $this->components->warn(
                'config/env-settings.php is not published, so the class could not be registered automatically. '
                ."Publish it with `php artisan vendor:publish --tag=env-settings-config`, then add \\{$fqcn}::class to the `register` array."
            );

            return;
        }

        $content = file_get_contents($configPath);

        if ($content === false) {
            $this->components->warn("Could not read {$configPath}; register \\{$fqcn}::class manually.");

            return;
        }

        $alreadyRegistered = false;

        $newContent = preg_replace_callback(
            "/'register'\s*=>\s*\[([\s\S]*?)\s*\]/",
            function (array $matches) use ($fqcn, &$alreadyRegistered): string {
                if (in_array($fqcn, $this->registeredClasses($matches[1]), true)) {
                    $alreadyRegistered = true;

                    return $matches[0];
                }

                $inner = rtrim($matches[1]);

                return "'register' => [{$inner}\n        \\{$fqcn}::class,\n    ]";
            },
            $content,
            1,
            $count,
        );

        if ($count !== 1 || $newContent === null) {
            $this->components->warn(
                "Could not find a `register` array in {$configPath}; add \\{$fqcn}::class to it manually."
            );

            return;
        }

        if ($alreadyRegistered) {
            $this->line("  → \\{$fqcn}::class is already registered in config/env-settings.php");

            return;
        }

        file_put_contents($configPath, $newContent);
        $this->line("  → Registered \\{$fqcn}::class in config/env-settings.php");
    }

    /**
     * List the classes actually registered in a `register` array body.
     *
     * Comments are stripped first: the published config ships commented-out
     * examples such as `// \App\Settings\AuthSettings::class,`, and treating
     * those as real entries would silently skip registering a class of the
     * same name.
     *
     * @return list<string>
     */
    private function registeredClasses(string $registerBody): array
    {
        $body = preg_replace('%/\*[\s\S]*?\*/%', '', $registerBody) ?? $registerBody;
        $body = preg_replace('%(//|\#).*$%m', '', $body) ?? $body;

        preg_match_all(
            '/([A-Za-z_\x80-\xff\\\\][A-Za-z0-9_\x80-\xff\\\\]*)\s*::\s*class/',
            $body,
            $matches
        );

        return array_values(array_map(
            static fn (string $class): string => ltrim($class, '\\'),
            $matches[1]
        ));
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
