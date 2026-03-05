<?php

declare(strict_types=1);

namespace Tests\Framework\Attributes;

use Framework\Attributes\Service;
use PHPUnit\Framework\TestCase;

#[Service]
class ServiceDummy {}

final class ServiceTest extends TestCase
{
    public function testServiceAttributeApplied(): void
    {
        $attrs = (new \ReflectionClass(ServiceDummy::class))->getAttributes(Service::class);
        $this->assertCount(1, $attrs);
    }
}
