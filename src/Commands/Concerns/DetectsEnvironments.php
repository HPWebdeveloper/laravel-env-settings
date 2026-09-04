<?php

declare(strict_types=1);

namespace HpWebDeveloper\LaravelEnvSettings\Commands\Concerns;

use HpWebDeveloper\LaravelEnvSettings\EnvironmentSettings;
use ReflectionClass;
use ReflectionMethod;

/**
 * Lists the environment factories a settings class defines.
 *
 * Shared so `env-settings:diff` and `env-settings:check` agree on what counts
 * as an environment.
 */
trait DetectsEnvironments
{
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

        // Always append staging/testing if the class defines them beyond the default
        foreach (['staging', 'testing'] as $extra) {
            if (method_exists($class, $extra) && ! in_array($extra, $methods, true)) {
                $methods[] = $extra;
            }
        }

        return empty($methods) ? ['development', 'production'] : $methods;
    }
}
