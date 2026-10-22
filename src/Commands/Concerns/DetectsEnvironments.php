<?php

declare(strict_types=1);

namespace HpWebDeveloper\LaravelEnvSettings\Commands\Concerns;

use HpWebDeveloper\LaravelEnvSettings\EnvironmentSettings;
use ReflectionClass;
use ReflectionMethod;

/**
 * Lists the environment factories a settings class defines.
 *
 * Shared so `env-settings:show --all`, `env-settings:diff` and
 * `env-settings:check` agree on what counts as an environment.
 */
trait DetectsEnvironments
{
    /**
     * The factories every settings class inherits, each returning development().
     *
     * @var list<string>
     */
    private const OPTIONAL_ENVIRONMENTS = ['staging', 'testing'];

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

            // getMethods() filters with OR, so public instance methods and
            // private static ones come through too; a factory must be both.
            if (! $method->isPublic() || ! $method->isStatic()) {
                continue;
            }

            if ($method->isAbstract() || in_array($name, $skip, true) || str_starts_with($name, '__')) {
                continue;
            }

            if ($method->getDeclaringClass()->getName() === EnvironmentSettings::class) {
                continue;
            }

            $methods[] = $name;
        }

        // Append staging/testing only if the class defines them beyond the
        // default. method_exists() would see the inherited ones on every class,
        // listing two environments that only repeat development().
        foreach (self::OPTIONAL_ENVIRONMENTS as $extra) {
            if ($this->definesEnvironment($class, $extra) && ! in_array($extra, $methods, true)) {
                $methods[] = $extra;
            }
        }

        return empty($methods) ? ['development', 'production'] : $methods;
    }

    /**
     * The optional factories the class leaves to the base class.
     *
     * Each one listed returns development(), so APP_ENV values that reach it
     * run development's values.
     *
     * @param  class-string<EnvironmentSettings>  $class
     * @return list<string>
     */
    private function inheritedEnvironments(string $class): array
    {
        return array_values(array_filter(
            self::OPTIONAL_ENVIRONMENTS,
            fn (string $name): bool => ! $this->definesEnvironment($class, $name),
        ));
    }

    /**
     * Whether the factory is declared below EnvironmentSettings, by the class
     * or a parent of its own.
     *
     * @param  class-string<EnvironmentSettings>  $class
     */
    private function definesEnvironment(string $class, string $method): bool
    {
        return (new ReflectionMethod($class, $method))->getDeclaringClass()->getName() !== EnvironmentSettings::class;
    }
}
