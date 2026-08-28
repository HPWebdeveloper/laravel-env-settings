<?php

declare(strict_types=1);

namespace HpWebDeveloper\LaravelEnvSettings\Commands\Concerns;

use HpWebDeveloper\LaravelEnvSettings\Attributes\Sensitive;
use ReflectionProperty;

/**
 * Decides whether a settings property's value may be shown in console output.
 *
 * Shared by the commands so `env-settings:show` and `env-settings:diff` hide
 * the same values.
 */
trait MasksSensitiveValues
{
    private const MASK = '********';

    /**
     * Property-name fragments treated as sensitive when nothing is marked.
     *
     * Retained so settings written before {@see Sensitive} existed keep their
     * values hidden. It is a guess, not a rule — mark the property to be sure.
     *
     * @var list<string>
     */
    private const SENSITIVE_NAME_FRAGMENTS = ['key', 'secret', 'password', 'token'];

    /**
     * Mask a rendered value when its property must not be displayed.
     *
     * A marked property is masked whatever it holds, because the developer
     * said so. The name fallback is deliberately limited to strings, matching
     * the behaviour it replaces: `max_tokens` and `token_count` are numbers
     * that happen to contain a flagged word, and hiding them would be wrong.
     *
     * Empty strings are left alone — printing a mask for a value that was
     * never set would be misleading.
     */
    private function maskIfSensitive(ReflectionProperty $property, mixed $value, string $rendered): string
    {
        if ($rendered === '') {
            return $rendered;
        }

        if ($property->getAttributes(Sensitive::class) !== []) {
            return self::MASK;
        }

        if (is_string($value) && $value !== '' && $this->nameLooksSensitive($property->getName())) {
            return self::MASK;
        }

        return $rendered;
    }

    private function nameLooksSensitive(string $name): bool
    {
        $name = strtolower($name);

        foreach (self::SENSITIVE_NAME_FRAGMENTS as $fragment) {
            if (str_contains($name, $fragment)) {
                return true;
            }
        }

        return false;
    }
}
