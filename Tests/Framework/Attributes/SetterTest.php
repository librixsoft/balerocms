<?php

declare(strict_types=1);

namespace Tests\Framework\Attributes;

use Framework\DTO\Attributes\Setter;
use PHPUnit\Framework\TestCase;

#[Setter]
class SetterDummy {}

final class SetterTest extends TestCase
{
    public function testSetterAttributeApplied(): void
    {
        $attrs = (new \ReflectionClass(SetterDummy::class))->getAttributes(Setter::class);
        $this->assertCount(1, $attrs);
    }
}
