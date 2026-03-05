<?php

declare(strict_types=1);

namespace Tests\Framework\Attributes;

use Framework\Attributes\Inject;
use PHPUnit\Framework\TestCase;

class InjectDummy
{
    #[Inject]
    public ?string $prop = null;
}

final class InjectTest extends TestCase
{
    public function testInjectAttributeApplied(): void
    {
        $attrs = (new \ReflectionProperty(InjectDummy::class, 'prop'))->getAttributes(Inject::class);
        $this->assertCount(1, $attrs);
    }
}
