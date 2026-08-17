<?php

declare(strict_types=1);

namespace HpWebDeveloper\LaravelEnvSettings\Tests\Fixtures\Overrides;

use HpWebDeveloper\LaravelEnvSettings\Tests\Fixtures\FakeAuthSettings as BaseFakeAuthSettings;

final class FakeAuthSettings extends BaseFakeAuthSettings
{
    public static function development(): static
    {
        return new self(
            domain: 'my-local-override.test',
            redirect_url: 'http://my-local:8080/callback',
            timeout: 60,
            mfa_enabled: false,
        );
    }
}
