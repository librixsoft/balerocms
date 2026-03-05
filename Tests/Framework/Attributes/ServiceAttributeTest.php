<?php

declare(strict_types=1);

namespace Tests\Framework\Attributes;

use Framework\Attributes\Service;
use PHPUnit\Framework\TestCase;

#[Service]
class ServiceAttributeDummy {}

final class ServiceAttributeTest extends TestCase
{
    public function testServiceAttributeCanBeAppliedToClass(): void
    {
        $r = new \ReflectionClass(ServiceAttributeDummy::class);
        $attrs = $r->getAttributes(Service::class);

        $this->assertCount(1, $attrs);
    }
}
