<?php

declare(strict_types=1);

namespace HpWebDeveloper\LaravelEnvSettings\Attributes;

use Attribute;

/**
 * Declares that an empty value for this property is intentional.
 *
 * `env-settings:check` reports a property left at its generated placeholder
 * ('' , 0, false, []) when another environment supplies a real value, on the
 * assumption that one environment was filled in and another forgotten. Where
 * the emptiness is deliberate — an optional prefix, a feature left off in one
 * environment — mark the property and the check stays quiet:
 *
 *     public function __construct(
 *         #[AllowEmpty] public string $path_prefix,
 *     ) {}
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
final class AllowEmpty {}
