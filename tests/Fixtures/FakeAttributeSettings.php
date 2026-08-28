<?php

declare(strict_types=1);

namespace HpWebDeveloper\LaravelEnvSettings\Tests\Fixtures;

use HpWebDeveloper\LaravelEnvSettings\Attributes\Environment;
use HpWebDeveloper\LaravelEnvSettings\EnvironmentSettings;

/**
 * Extended by the Overrides fixture, so it cannot be final. The subclass keeps
 * the same constructor, which is what makes `new static()` safe.
 *
 * @phpstan-consistent-constructor
 */
class FakeAttributeSettings extends EnvironmentSettings
{
    public function __construct(public string $tier) {}

    public static function development(): static
    {
        return new static('dev');
    }

    #[Environment('production', 'prod')]
    #[Environment('canary')]
    public static function production(): static
    {
        return new static('prod');
    }

    // Marked but not static, and marked but not public. getMethods() filters
    // with OR, so both reach the resolver; calling either as a factory would
    // raise an Error, so they must be ignored.
    #[Environment('bad-instance')]
    public function notStatic(): static
    {
        return new static('instance');
    }

    #[Environment('bad-protected')]
    protected static function notPublic(): static
    {
        return new static('private');
    }

    // Method name deliberately unlike any APP_ENV value, serving two of them.
    #[Environment('qa', 'uat')]
    public static function qualityAssurance(): static
    {
        return new static('qa');
    }
}
