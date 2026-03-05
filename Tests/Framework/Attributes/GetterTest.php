<?php

declare(strict_types=1);

namespace Tests\Framework\Attributes;

use Framework\DTO\Attributes\Getter;
use PHPUnit\Framework\TestCase;

#[Getter]
class GetterDummy {}

final class GetterTest extends TestCase
{
    public function testGetterAttributeApplied(): void
    {
        $attrs = (new \ReflectionClass(GetterDummy::class))->getAttributes(Getter::class);
        $this->assertCount(1, $attrs);
    }
}
