<?php

declare(strict_types=1);

namespace HpWebDeveloper\LaravelEnvSettings\Attributes;

use Attribute;

/**
 * Marks a settings property as sensitive so its value is masked in console output.
 *
 * Without it, the commands fall back to matching property names against a list
 * of substrings, which both over- and under-matches: `monkey_api_url` contains
 * "key" and is masked needlessly, while `passphrase`, `credentials` and
 * `bearer` are printed in full. Marking the property says so outright:
 *
 *     public function __construct(
 *         #[Sensitive] public string $passphrase,
 *     ) {}
 *
 * Only display is affected. `toArray()` still returns the real value, so
 * serialising settings keeps working.
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
final class Sensitive {}
