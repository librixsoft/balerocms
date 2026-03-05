<?php

declare(strict_types=1);

namespace Tests\Framework\Attributes;

use Framework\DTO\Attributes\Setter;
use PHPUnit\Framework\TestCase;

#[Setter]
class SetterAttributeDummy {}

final class SetterAttributeTest extends TestCase
{
    public function testSetterAttributeCanBeAppliedToClass(): void
    {
        $r = new \ReflectionClass(SetterAttributeDummy::class);
        $attrs = $r->getAttributes(Setter::class);

        $this->assertCount(1, $attrs);
    }
}
