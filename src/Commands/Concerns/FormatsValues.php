<?php

declare(strict_types=1);

namespace HpWebDeveloper\LaravelEnvSettings\Commands\Concerns;

use BackedEnum;
use HpWebDeveloper\LaravelEnvSettings\EnvironmentSettings;
use Throwable;
use UnitEnum;

/**
 * Renders a resolved setting value for console output, and decides when two
 * of them are the same.
 *
 * Shared so the commands cannot drift apart. `env-settings:show` and
 * `env-settings:diff` each carried their own copy, and the one in `diff` was
 * missing the last two guards below — which crashed the command outright on an
 * object, and made a null compare equal to an empty string so a real difference
 * went unreported (fixed in v1.5.1).
 */
trait FormatsValues
{
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

        if ($value instanceof BackedEnum) {
            return (string) $value->value;
        }

        if ($value instanceof UnitEnum) {
            return $value->name;
        }

        if (is_object($value) && ! method_exists($value, '__toString')) {
            return '[object: '.get_class($value).']';
        }

        if ($value === null) {
            return 'null';
        }

        return (string) $value;
    }

    /**
     * Whether formatValue() shows only a placeholder for the value.
     *
     * A nested settings class or an object with no string form renders as its
     * class name, so two different values can render alike and a comparison
     * of the rendered strings cannot tell them apart.
     */
    private function rendersAsPlaceholder(mixed $value): bool
    {
        return $value instanceof EnvironmentSettings
            || (is_object($value) && ! $value instanceof UnitEnum && ! method_exists($value, '__toString'));
    }

    /**
     * A string that two values share exactly when they hold the same setting.
     *
     * Most values compare by how they render. A nested settings class or an
     * object renders as its class name, so it is compared by its state
     * instead — otherwise a composed class whose children differ in every
     * environment is reported as never varying, and `diff` finds nothing.
     */
    private function comparisonKey(mixed $value): string
    {
        if (! $this->rendersAsPlaceholder($value)) {
            return $this->formatValue($value);
        }

        try {
            return serialize($value);
        } catch (Throwable) {
            // A closure, or another object PHP refuses to serialise: all that
            // is left to compare is the class name.
            return $this->formatValue($value);
        }
    }
}
