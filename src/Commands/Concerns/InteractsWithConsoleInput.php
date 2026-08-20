<?php

declare(strict_types=1);

namespace HpWebDeveloper\LaravelEnvSettings\Commands\Concerns;

use InvalidArgumentException;

/**
 * Typed access to console input.
 *
 * `Command::argument()` and `Command::option()` are declared as returning a
 * union wide enough to cover array arguments and boolean flags. Every
 * signature in this package declares single-value arguments and options, so
 * the union is narrowed here once rather than at each call site.
 *
 * A value of the wrong shape means the command signature and the code that
 * reads it have drifted apart, so it fails loudly instead of being coerced.
 */
trait InteractsWithConsoleInput
{
    /**
     * Read a single-value argument, which may be absent.
     */
    private function stringArgument(string $name): ?string
    {
        $value = $this->argument($name);

        if ($value === null || is_string($value)) {
            return $value;
        }

        throw new InvalidArgumentException("The [{$name}] argument must be a single value.");
    }

    /**
     * Read a single-value argument that the signature declares as required.
     */
    private function requiredStringArgument(string $name): string
    {
        $value = $this->stringArgument($name);

        if ($value === null) {
            throw new InvalidArgumentException("The [{$name}] argument is required.");
        }

        return $value;
    }

    /**
     * Read a single-value option, which may be absent.
     */
    private function stringOption(string $name): ?string
    {
        $value = $this->option($name);

        if ($value === null || is_string($value)) {
            return $value;
        }

        throw new InvalidArgumentException("The [{$name}] option must be a single value.");
    }
}
