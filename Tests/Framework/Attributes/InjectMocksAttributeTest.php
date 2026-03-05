<?php

declare(strict_types=1);

namespace Tests\Framework\Attributes;

use Framework\Attributes\InjectMocks;
use PHPUnit\Framework\TestCase;

class InjectMocksAttributeDummy
{
    #[InjectMocks]
    public ?object $sut = null;
}

final class InjectMocksAttributeTest extends TestCase
{
    public function testInjectMocksCanBeAppliedToProperty(): void
    {
        $prop = new \ReflectionProperty(InjectMocksAttributeDummy::class, 'sut');
        $attrs = $prop->getAttributes(InjectMocks::class);

        $this->assertCount(1, $attrs);
    }
}
