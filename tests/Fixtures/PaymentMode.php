<?php

declare(strict_types=1);

namespace HpWebDeveloper\LaravelEnvSettings\Tests\Fixtures;

enum PaymentMode: string
{
    case Live = 'live';
    case Sandbox = 'sandbox';
}
