<?php

declare(strict_types=1);

namespace HpWebDeveloper\LaravelEnvSettings\Tests\Fixtures\Overrides;

use HpWebDeveloper\LaravelEnvSettings\Tests\Fixtures\FakeAttributeSettings as BaseFakeAttributeSettings;

/**
 * Redeclares a marked factory without repeating the attribute, which is what a
 * developer writing a local override naturally does.
 */
final class FakeAttributeSettings extends BaseFakeAttributeSettings
{
    public static function qualityAssurance(): static
    {
        return new self('overridden-qa');
    }
}
