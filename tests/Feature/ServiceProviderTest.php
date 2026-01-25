<?php

declare(strict_types=1);

namespace Vendor\Package\Tests\Feature;

use Vendor\Package\Tests\TestCase;

class ServiceProviderTest extends TestCase
{
    public function test_config_is_merged(): void
    {
        $this->assertIsArray(config('package'));
        $this->assertArrayHasKey('defaults', config('package'));
    }
}
