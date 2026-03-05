<?php

declare(strict_types=1);

namespace Tests\Framework\Attributes;

use Framework\Attributes\SetupTestContainer;
use PHPUnit\Framework\TestCase;

#[SetupTestContainer('X\\Y\\Container')]
class SetupTestContainerDummy2 {}

final class SetupTestContainerTest extends TestCase
{
    public function testSetupTestContainerStoresClassName(): void
    {
        $attr = (new \ReflectionClass(SetupTestContainerDummy2::class))->getAttributes(SetupTestContainer::class)[0]->newInstance();
        $this->assertSame('X\\Y\\Container', $attr->containerClass);
    }
}
