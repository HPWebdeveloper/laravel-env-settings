<?php

declare(strict_types=1);

namespace HpWebDeveloper\LaravelEnvSettings;

use HpWebDeveloper\LaravelEnvSettings\Attributes\Environment;
use HpWebDeveloper\LaravelEnvSettings\Support\Path;
use Illuminate\Support\Facades\File;
use ReflectionClass;
use ReflectionMethod;

class EnvSettingsResolver
{
    /**
     * Environment name to factory method, per settings class.
     *
     * Attributes cannot change while the process runs, so each class is
     * reflected once. Held on the instance rather than statically because the
     * resolver is a container singleton — the cache lives and dies with the
     * application, and does not leak between tests.
     *
     * @var array<class-string, array<string, string>>
     */
    private array $environmentMethods = [];

    /**
     * Resolve the correct settings instance for the current environment.
     *
     * Resolution order:
     *   1. If overrides are enabled and an override class exists, delegate to it.
     *   2. A method marked #[Environment] for the current APP_ENV.
     *   3. The APP_ENV mapped through `env-settings.environment_map`, falling
     *      back to the APP_ENV name itself as a method name.
     *   4. `fallback_environment`, then `development()`.
     *
     * Uses `app()->environment()` and `config()` throughout — never `env()` —
     * so it is fully compatible with `php artisan config:cache`.
     *
     * @template T of EnvironmentSettings
     *
     * @param  class-string<T>  $class
     * @return T
     */
    public function resolve(string $class): EnvironmentSettings
    {
        $target = $class;

        if ($this->shouldUseOverride()) {
            $overrideClass = $this->resolveOverrideClass($class);

            if ($overrideClass !== null) {
                $target = $overrideClass;
            }
        }

        return $this->resolveForEnvironment($target);
    }

    /**
     * @template T of EnvironmentSettings
     *
     * @param  class-string<T>  $class
     * @return T
     */
    private function resolveForEnvironment(string $class): EnvironmentSettings
    {
        $appEnv = app()->environment();

        // A method marked #[Environment] for this APP_ENV wins: the class has
        // said outright which environments it serves, so it should not be
        // overridden by a map it cannot see.
        $declared = $this->declaredEnvironmentMethods($class)[$appEnv] ?? null;

        if ($declared !== null) {
            return $class::{$declared}();
        }

        $map = config('env-settings.environment_map', []);
        $mapped = $map[$appEnv] ?? $appEnv;

        if (method_exists($class, $mapped)) {
            return $class::{$mapped}();
        }

        $fallback = config('env-settings.fallback_environment', 'development');

        if (method_exists($class, $fallback)) {
            return $class::{$fallback}();
        }

        return $class::development();
    }

    private function shouldUseOverride(): bool
    {
        return (bool) config('env-settings.override', false);
    }

    /**
     * Read the #[Environment] declarations for a settings class.
     *
     * The hierarchy is walked because PHP does not inherit attributes onto an
     * overridden method: a local override that redeclares a marked factory
     * would otherwise lose its mapping and silently resolve to development.
     * Only the mapping is inherited — the method is still called on the
     * subclass, so the override's own values are used.
     *
     * Declarations closest to the class win. Where two methods claim the same
     * environment, the first one declared wins.
     *
     * @param  class-string<EnvironmentSettings>  $class
     * @return array<string, string> environment name => method name
     */
    private function declaredEnvironmentMethods(string $class): array
    {
        if (array_key_exists($class, $this->environmentMethods)) {
            return $this->environmentMethods[$class];
        }

        $methods = [];

        for ($current = new ReflectionClass($class); $current !== false; $current = $current->getParentClass()) {
            foreach ($current->getMethods(ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_STATIC) as $method) {
                // Skip methods inherited into $current; each class in the chain
                // contributes only what it declares itself.
                if ($method->getDeclaringClass()->getName() !== $current->getName()) {
                    continue;
                }

                // getMethods() filters with OR, so it also yields public
                // instance methods and private static ones. Calling either as
                // a factory raises an Error, so ignore anything that is not
                // both public and static.
                if (! $method->isPublic() || ! $method->isStatic()) {
                    continue;
                }

                foreach ($method->getAttributes(Environment::class) as $attribute) {
                    foreach ($attribute->newInstance()->names as $name) {
                        $methods[$name] ??= $method->getName();
                    }
                }
            }
        }

        return $this->environmentMethods[$class] = $methods;
    }

    /**
     * Look for an override class for the given settings class.
     *
     * Requires explicit `override_path` and `override_namespace` configuration —
     * no filesystem scanning or namespace derivation from reflection.
     *
     * The result is checked to be a subclass of the class being resolved, so
     * it stands in for it everywhere the original would have been used.
     *
     * @template T of EnvironmentSettings
     *
     * @param  class-string<T>  $class
     * @return class-string<T>|null
     */
    private function resolveOverrideClass(string $class): ?string
    {
        $overridePath = $this->resolveOverridePath();

        if (! $overridePath || ! is_dir($overridePath)) {
            return null;
        }

        $shortName = class_basename($class);
        $filePath = rtrim($overridePath, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$shortName.'.php';

        if (! File::exists($filePath)) {
            return null;
        }

        $overrideNamespace = config('env-settings.override_namespace');

        if (! $overrideNamespace) {
            return null;
        }

        $overrideClass = rtrim($overrideNamespace, '\\').'\\'.$shortName;

        if (! class_exists($overrideClass) || ! is_subclass_of($overrideClass, $class)) {
            return null;
        }

        return $overrideClass;
    }

    /**
     * Resolve the directory that override classes are loaded from.
     *
     * The configured value is interpreted at runtime — after the application has
     * booted — so no path helper is ever called at config-load time. This keeps
     * `php artisan config:cache` portable: nothing app-relative is frozen into
     * `bootstrap/cache/config.php`, so a cache built in CI or a Docker build
     * stage stays correct when the app runs from a different absolute path.
     *
     *   null                    → app_path('Settings/Overrides')
     *   'Custom/Overrides'      → app_path('Custom/Overrides')
     *   '/mnt/shared/overrides' → used as-is
     */
    private function resolveOverridePath(): ?string
    {
        $configured = config('env-settings.override_path');

        if ($configured === null || $configured === '') {
            return app_path('Settings/Overrides');
        }

        if (! is_string($configured)) {
            return null;
        }

        return Path::isAbsolute($configured)
            ? $configured
            : app_path($configured);
    }
}
