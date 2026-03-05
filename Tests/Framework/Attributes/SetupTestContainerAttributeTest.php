<?php

declare(strict_types=1);

namespace Tests\Framework\Attributes;

use Framework\Attributes\SetupTestContainer;
use PHPUnit\Framework\TestCase;

#[SetupTestContainer('Custom\\Container')]
class SetupTestContainerDummy {}

final class SetupTestContainerAttributeTest extends TestCase
{
    public function testSetupTestContainerStoresContainerClass(): void
    {
        $attr = (new \ReflectionClass(SetupTestContainerDummy::class))
            ->getAttributes(SetupTestContainer::class)[0]
            ->newInstance();

        $this->assertSame('Custom\\Container', $attr->containerClass);
        $this->assertNull((new SetupTestContainer())->containerClass);
    }
}
