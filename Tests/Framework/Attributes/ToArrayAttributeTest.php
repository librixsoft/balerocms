<?php

declare(strict_types=1);

namespace Tests\Framework\Attributes;

use Framework\DTO\Attributes\ToArray;
use PHPUnit\Framework\TestCase;

#[ToArray]
class ToArrayAttributeDummy {}

final class ToArrayAttributeTest extends TestCase
{
    public function testToArrayAttributeCanBeAppliedToClass(): void
    {
        $r = new \ReflectionClass(ToArrayAttributeDummy::class);
        $attrs = $r->getAttributes(ToArray::class);

        $this->assertCount(1, $attrs);
    }
}
