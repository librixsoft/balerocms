<?php

declare(strict_types=1);

namespace Tests\Framework\Attributes;

use Framework\Attributes\DTO;
use PHPUnit\Framework\TestCase;

#[DTO]
class DTODummy {}

final class DTOTest extends TestCase
{
    public function testDtoAttributeApplied(): void
    {
        $attrs = (new \ReflectionClass(DTODummy::class))->getAttributes(DTO::class);
        $this->assertCount(1, $attrs);
    }
}
