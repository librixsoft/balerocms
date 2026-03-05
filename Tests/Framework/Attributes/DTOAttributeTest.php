<?php

declare(strict_types=1);

namespace Tests\Framework\Attributes;

use Framework\Attributes\DTO;
use PHPUnit\Framework\TestCase;

#[DTO]
class DTOAttributeDummy {}

final class DTOAttributeTest extends TestCase
{
    public function testDtoAttributeCanBeAppliedToClass(): void
    {
        $r = new \ReflectionClass(DTOAttributeDummy::class);
        $attrs = $r->getAttributes(DTO::class);

        $this->assertCount(1, $attrs);
    }
}
