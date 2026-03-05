<?php

declare(strict_types=1);

namespace Tests\Framework\Attributes;

use Framework\Attributes\Controller;
use PHPUnit\Framework\TestCase;

#[Controller('/admin')]
class ControllerAttributeDummy {}

final class ControllerAttributeTest extends TestCase
{
    public function testControllerAttributeStoresPathUrl(): void
    {
        $attr = (new \ReflectionClass(ControllerAttributeDummy::class))
            ->getAttributes(Controller::class)[0]
            ->newInstance();

        $this->assertSame('/admin', $attr->pathUrl);
        $this->assertSame('/', (new Controller())->pathUrl);
    }
}
