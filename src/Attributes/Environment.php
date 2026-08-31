<?php

declare(strict_types=1);

namespace HpWebDeveloper\LaravelEnvSettings\Attributes;

use Attribute;

/**
 * Declares which `APP_ENV` values a factory method serves.
 *
 * Without it, the link between an environment and the method that handles it
 * lives in the application's `environment_map` config — a different file, and
 * a different repository when the settings class ships in a package. Reading
 * the class tells you the method names but not which environments reach them,
 * so the same class can resolve differently in two applications.
 *
 * Marking the method keeps that answer beside the code:
 *
 *     #[Environment('production', 'prod')]
 *     public static function production(): static { ... }
 *
 *     #[Environment('qa', 'uat')]
 *     public static function qualityAssurance(): static { ... }
 *
 * The attribute is repeatable, so a method may also be marked several times.
 * Environment names are matched case-sensitively, as `APP_ENV` is.
 */
#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final class Environment
{
    /**
     * @var list<string>
     */
    public readonly array $names;

    public function __construct(string ...$names)
    {
        $this->names = array_values($names);
    }
}
