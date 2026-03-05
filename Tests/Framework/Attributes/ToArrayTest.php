<?php

declare(strict_types=1);

namespace Tests\Framework\Attributes;

use Framework\DTO\Attributes\ToArray;
use PHPUnit\Framework\TestCase;

#[ToArray]
class ToArrayDummy {}

final class ToArrayTest extends TestCase
{
    public function testToArrayAttributeApplied(): void
    {
        $attrs = (new \ReflectionClass(ToArrayDummy::class))->getAttributes(ToArray::class);
        $this->assertCount(1, $attrs);
    }
}
