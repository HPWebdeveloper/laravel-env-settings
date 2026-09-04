<?php

declare(strict_types=1);

namespace HpWebDeveloper\LaravelEnvSettings\Tests\Fixtures;

/** Pure enum: no backing value, so json_encode cannot serialise it. */
enum Tier
{
    case Low;
    case High;
}
