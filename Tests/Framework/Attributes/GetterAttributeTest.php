<?php

declare(strict_types=1);

namespace Tests\Framework\Attributes;

use Framework\DTO\Attributes\Getter;
use PHPUnit\Framework\TestCase;

#[Getter]
class GetterAttributeDummy {}

final class GetterAttributeTest extends TestCase
{
    public function testGetterAttributeCanBeAppliedToClass(): void
    {
        $r = new \ReflectionClass(GetterAttributeDummy::class);
        $attrs = $r->getAttributes(Getter::class);

        $this->assertCount(1, $attrs);
    }
}
