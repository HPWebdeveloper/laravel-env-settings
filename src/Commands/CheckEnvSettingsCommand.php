<?php

declare(strict_types=1);

namespace HpWebDeveloper\LaravelEnvSettings\Commands;

use HpWebDeveloper\LaravelEnvSettings\Attributes\AllowEmpty;
use HpWebDeveloper\LaravelEnvSettings\Commands\Concerns\DetectsEnvironments;
use HpWebDeveloper\LaravelEnvSettings\Commands\Concerns\InteractsWithConsoleInput;
use HpWebDeveloper\LaravelEnvSettings\EnvironmentSettings;
use Illuminate\Console\Command;
use ReflectionClass;
use ReflectionProperty;
use Throwable;

use function Laravel\Prompts\error;

class CheckEnvSettingsCommand extends Command
{
    use DetectsEnvironments;
    use InteractsWithConsoleInput;

    protected $signature = 'env-settings:check
        {class? : Limit the check to one settings class}
        {--env= : Environment to check (default: the current APP_ENV)}';

    protected $description = 'Report settings left at their generated placeholder for an environment';

    public function handle(): int
    {
        $environment = $this->stringOption('env') ?? app()->environment();
        $classes = $this->classesToCheck();

        if ($classes === null) {
            return self::FAILURE;
        }

        if ($classes === []) {
            $this->components->warn("No settings classes registered in config('env-settings.register').");

            return self::SUCCESS;
        }

        $findings = [];

        foreach ($classes as $class) {
            $incomplete = $this->incompleteProperties($class, $environment);

            if ($incomplete !== []) {
                $findings[$class] = $incomplete;
            }
        }

        return $this->report($findings, $environment, count($classes));
    }

    /**
     * @param  array<class-string<EnvironmentSettings>, array<string, string>>  $findings
     */
    private function report(array $findings, string $environment, int $checked): int
    {
        if ($findings === []) {
            $this->info("[ {$environment} ] — {$checked} settings ".($checked === 1 ? 'class' : 'classes').' complete.');

            return self::SUCCESS;
        }

        $values = 0;

        foreach ($findings as $class => $properties) {
            $this->newLine();
            $this->line("<fg=red>✗</> {$class}");

            foreach ($properties as $property => $reason) {
                $this->line(sprintf('    <fg=yellow>%-24s</> %s', $property, $reason));
                $values++;
            }
        }

        $this->newLine();
        $this->line(sprintf(
            '%d of %d classes incomplete for [%s]: %d value%s to fill in.',
            count($findings),
            $checked,
            $environment,
            $values,
            $values === 1 ? '' : 's',
        ));
        $this->line('Mark a property #[AllowEmpty] if its empty value is intentional.');

        return self::FAILURE;
    }

    /**
     * The registered settings classes, or the single one named on the command.
     *
     * @return list<class-string<EnvironmentSettings>>|null null when the named
     *                                                      class is unusable
     */
    private function classesToCheck(): ?array
    {
        $named = $this->stringArgument('class');

        if ($named !== null) {
            if (! class_exists($named) || ! is_subclass_of($named, EnvironmentSettings::class)) {
                error("Class {$named} is not a valid EnvironmentSettings subclass.");

                return null;
            }

            return [$named];
        }

        $registered = config('env-settings.register', []);

        if (! is_array($registered)) {
            return [];
        }

        $classes = [];

        foreach ($registered as $candidate) {
            if (is_string($candidate) && class_exists($candidate) && is_subclass_of($candidate, EnvironmentSettings::class)) {
                $classes[] = $candidate;
            }
        }

        return $classes;
    }

    /**
     * Find properties left at their placeholder for the target environment.
     *
     * A placeholder alone is not evidence of a mistake — `retry_attempts: 0` may
     * be exactly what every environment wants. It only counts when *another*
     * environment supplies a real value for the same property, which is the
     * shape of a class where one factory was filled in and another forgotten.
     *
     * @param  class-string<EnvironmentSettings>  $class
     * @return array<string, string> property name => reason
     */
    private function incompleteProperties(string $class, string $environment): array
    {
        $target = $this->resolveIn($class, $environment);

        if ($target === null) {
            return [];
        }

        $others = [];

        foreach ($this->detectEnvironments($class) as $other) {
            $instance = $this->instanceFor($class, $other);

            if ($instance !== null) {
                $others[$other] = $instance;
            }
        }

        $findings = [];

        foreach ((new ReflectionClass($target))->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            if ($property->isStatic() || $property->getAttributes(AllowEmpty::class) !== []) {
                continue;
            }

            $value = $property->getValue($target);

            if (is_string($value) && str_contains(strtoupper($value), 'TODO')) {
                $findings[$property->getName()] = 'still contains "TODO"';

                continue;
            }

            if (! $this->isPlaceholder($value)) {
                continue;
            }

            foreach ($others as $name => $instance) {
                if ($instance == $target) {
                    continue;
                }

                if (! $this->isPlaceholder($property->getValue($instance))) {
                    $findings[$property->getName()] = sprintf('%s, but set in %s()', $this->describe($value), $name);

                    break;
                }
            }
        }

        return $findings;
    }

    /**
     * Values the generator writes when it has nothing to put there yet.
     */
    private function isPlaceholder(mixed $value): bool
    {
        return $value === '' || $value === 0 || $value === 0.0 || $value === false || $value === [] || $value === null;
    }

    private function describe(mixed $value): string
    {
        return match (true) {
            $value === '' => 'empty string',
            $value === [] => 'empty array',
            $value === null => 'null',
            $value === false => 'false',
            default => (string) $value,
        };
    }

    /**
     * Resolve a class as it would resolve with APP_ENV set to $environment.
     *
     * The application's environment is swapped so the real resolution path runs
     * — attributes, environment_map and overrides all included — then restored.
     *
     * @param  class-string<EnvironmentSettings>  $class
     */
    private function resolveIn(string $class, string $environment): ?EnvironmentSettings
    {
        $original = app()->environment();

        app()->detectEnvironment(fn (): string => $environment);

        try {
            return $class::resolve();
        } catch (Throwable) {
            return null;
        } finally {
            app()->detectEnvironment(fn (): string => $original);
        }
    }

    /**
     * Call a named factory directly, ignoring environment resolution.
     *
     * Exceptions are deliberately not caught: a factory that cannot build its
     * settings is itself something this command should surface, and swallowing
     * it here would silently reduce the comparison set and let an incomplete
     * environment pass.
     *
     * @param  class-string<EnvironmentSettings>  $class
     */
    private function instanceFor(string $class, string $factory): ?EnvironmentSettings
    {
        if (! method_exists($class, $factory)) {
            return null;
        }

        return $class::{$factory}();
    }
}
