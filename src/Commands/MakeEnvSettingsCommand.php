<?php

declare(strict_types=1);

namespace HpWebDeveloper\LaravelEnvSettings\Commands;

use HpWebDeveloper\LaravelEnvSettings\Commands\Concerns\InteractsWithConsoleInput;
use HpWebDeveloper\LaravelEnvSettings\Support\Path;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use RuntimeException;
use Throwable;

use function Laravel\Prompts\error;
use function Laravel\Prompts\outro;

class MakeEnvSettingsCommand extends Command
{
    use InteractsWithConsoleInput;

    protected $signature = 'env-settings:make
        {name : The name of the settings class (e.g. AuthSettings)}
        {--properties= : Comma-separated properties with types (e.g. domain:string,timeout:int,enabled:bool)}
        {--path= : Custom directory to create the file in (default: app/Settings)}
        {--namespace= : Explicit PHP namespace for the class (default: derived from --path, else config env-settings.class_namespace)}
        {--sensitive= : Comma-separated property names to mark #[Sensitive] (each must appear in --properties)}';

    protected $description = 'Create a new environment settings class';

    public function handle(Filesystem $files): int
    {
        $name = $this->requiredStringArgument('name');
        $path = $this->stringOption('path');
        $basePath = $path ?? app_path('Settings');
        $filePath = rtrim($basePath, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$name.'.php';

        if ($files->exists($filePath)) {
            error("Settings class already exists: {$filePath}");

            return self::FAILURE;
        }

        $properties = $this->parseProperties($this->stringOption('properties'));
        $sensitive = $this->parseSensitive($this->stringOption('sensitive'), $properties);

        if ($sensitive === null) {
            return self::FAILURE;
        }

        $namespace = $this->resolveNamespace($path);

        if ($namespace === null) {
            return self::FAILURE;
        }

        $stub = $this->buildStub($namespace, $name, $properties, $sensitive);

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
     * Parse and validate the --sensitive option.
     *
     * Every name must match a generated property. A typo that silently marked
     * nothing would leave a value unmasked while the developer believes
     * otherwise, so unknown names fail the command instead.
     *
     * @param  array<int, array{name: string, type: string}>  $properties
     * @return list<string>|null null on invalid input
     */
    private function parseSensitive(?string $raw, array $properties): ?array
    {
        if ($raw === null || trim($raw) === '') {
            return [];
        }

        $names = array_values(array_filter(array_map('trim', explode(',', $raw))));
        $known = array_column($properties, 'name');
        $unknown = array_diff($names, $known);

        if ($unknown !== []) {
            error('Unknown property in --sensitive: '.implode(', ', $unknown).'. Known properties: '.implode(', ', $known).'.');

            return null;
        }

        return $names;
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
     *
     * Returns null when `--namespace` is not a legal PHP namespace, which
     * the caller treats as a failure — writing the file anyway would leave
     * an unparseable class on disk.
     */
    private function resolveNamespace(?string $path): ?string
    {
        $explicit = $this->stringOption('namespace');

        if ($explicit !== null && trim($explicit) !== '') {
            $explicit = trim($explicit, '\\ ');

            if (! $this->isValidNamespace($explicit)) {
                error("Not a valid PHP namespace: {$explicit}");

                return null;
            }

            return $explicit;
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

        if (! is_string($configured) || ! $this->isValidNamespace(trim($configured, '\\ '))) {
            return 'App\\Settings';
        }

        return trim($configured, '\\ ');
    }

    /**
     * Determine whether a string is a legal PHP namespace.
     *
     * Guards every namespace before it reaches the stub — emitting an
     * invalid one produces a file that cannot be parsed at all.
     */
    private function isValidNamespace(string $namespace): bool
    {
        if ($namespace === '') {
            return false;
        }

        foreach (explode('\\', $namespace) as $segment) {
            if (preg_match('/^[A-Za-z_\x80-\xff][A-Za-z0-9_\x80-\xff]*$/', $segment) !== 1) {
                return false;
            }
        }

        return true;
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

        $derived = $rootNamespace.'\\'.str_replace('/', '\\', $relative);

        return $this->isValidNamespace($derived) ? $derived : null;
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

        $located = $this->locateRegisterBody($content);

        if ($located === null) {
            $this->components->warn(
                "Could not find a `register` array in {$configPath}; add \\{$fqcn}::class to it manually."
            );

            return;
        }

        [$start, $length] = $located;
        $body = substr($content, $start, $length);

        if (in_array($fqcn, $this->registeredClasses($body), true)) {
            $this->line("  → \\{$fqcn}::class is already registered in config/env-settings.php");

            return;
        }

        $newBody = rtrim($body)."\n        \\{$fqcn}::class,\n    ";

        file_put_contents($configPath, substr_replace($content, $newBody, $start, $length));
        $this->line("  → Registered \\{$fqcn}::class in config/env-settings.php");
    }

    /**
     * Locate the body of the `register` array within the config source.
     *
     * @return array{0: int, 1: int}|null offset and length of the text
     *                                    between the array's brackets
     */
    private function locateRegisterBody(string $content): ?array
    {
        if (preg_match("/'register'\s*=>\s*\[/", $content, $matches, PREG_OFFSET_CAPTURE) !== 1) {
            return null;
        }

        $start = $matches[0][1] + strlen($matches[0][0]);
        $end = $this->findClosingBracket($content, $start);

        return $end === null ? null : [$start, $end - $start];
    }

    /**
     * Find the `]` that closes an array whose body starts at $offset.
     *
     * Comments and quoted strings are skipped over, so a bracket inside
     * either cannot be mistaken for the end of the array — matching on the
     * first `]` would truncate the body and write back a corrupted file.
     */
    private function findClosingBracket(string $content, int $offset): ?int
    {
        $length = strlen($content);
        $depth = 0;
        $i = $offset;

        while ($i < $length) {
            $char = $content[$i];
            $next = $content[$i + 1] ?? '';

            if ($char === '#' || ($char === '/' && $next === '/')) {
                $lineEnd = strpos($content, "\n", $i);
                $i = $lineEnd === false ? $length : $lineEnd + 1;

                continue;
            }

            if ($char === '/' && $next === '*') {
                $close = strpos($content, '*/', $i + 2);

                if ($close === false) {
                    return null;
                }

                $i = $close + 2;

                continue;
            }

            if ($char === "'" || $char === '"') {
                $close = $this->findStringEnd($content, $i);

                if ($close === null) {
                    return null;
                }

                $i = $close + 1;

                continue;
            }

            if ($char === '[') {
                $depth++;
            } elseif ($char === ']') {
                if ($depth === 0) {
                    return $i;
                }

                $depth--;
            }

            $i++;
        }

        return null;
    }

    /**
     * Find the closing quote of the string literal starting at $offset.
     */
    private function findStringEnd(string $content, int $offset): ?int
    {
        $quote = $content[$offset];
        $length = strlen($content);

        for ($i = $offset + 1; $i < $length; $i++) {
            if ($content[$i] === '\\') {
                $i++;

                continue;
            }

            if ($content[$i] === $quote) {
                return $i;
            }
        }

        return null;
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
     * @param  list<string>  $sensitive
     */
    private function buildStub(string $namespace, string $class, array $properties, array $sensitive = []): string
    {
        $stubPath = __DIR__.'/../../stubs/env-settings.stub';
        $stub = file_get_contents($stubPath);

        if ($stub === false) {
            throw new RuntimeException("Unable to read the settings stub at {$stubPath}.");
        }

        $propsLines = [];
        $devValues = [];
        $prodValues = [];

        foreach ($properties as $prop) {
            $attribute = in_array($prop['name'], $sensitive, true) ? '#[Sensitive] ' : '';
            $propsLines[] = "        {$attribute}public {$prop['type']} \${$prop['name']},";
            $default = $this->defaultForType($prop['type']);
            $devValues[] = "            {$prop['name']}: {$default}, // TODO: set development value";
            $prodValues[] = "            {$prop['name']}: {$default}, // TODO: set production value";
        }

        $imports = $sensitive === []
            ? 'use HpWebDeveloper\\LaravelEnvSettings\\EnvironmentSettings;'
            : "use HpWebDeveloper\\LaravelEnvSettings\\Attributes\\Sensitive;\nuse HpWebDeveloper\\LaravelEnvSettings\\EnvironmentSettings;";

        return str_replace(
            ['{{ imports }}', '{{ namespace }}', '{{ class }}', '{{ properties }}', '{{ developmentValues }}', '{{ productionValues }}'],
            [$imports, $namespace, $class, implode("\n", $propsLines), implode("\n", $devValues), implode("\n", $prodValues)],
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
