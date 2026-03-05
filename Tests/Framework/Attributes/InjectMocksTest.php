<?php

declare(strict_types=1);

namespace Tests\Framework\Attributes;

use Framework\Attributes\InjectMocks;
use PHPUnit\Framework\TestCase;

class InjectMocksDummy
{
    #[InjectMocks]
    public ?object $sut = null;
}

final class InjectMocksTest extends TestCase
{
    public function testInjectMocksAttributeApplied(): void
    {
        $attrs = (new \ReflectionProperty(InjectMocksDummy::class, 'sut'))->getAttributes(InjectMocks::class);
        $this->assertCount(1, $attrs);
    }
}
