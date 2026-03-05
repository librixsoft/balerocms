<?php

declare(strict_types=1);

namespace Tests\Framework\Attributes;

use Framework\Attributes\Inject;
use PHPUnit\Framework\TestCase;

class InjectAttributeDummy
{
    #[Inject]
    public string $prop = '';

    public function __construct(#[Inject] public ?string $param = null)
    {
    }
}

final class InjectAttributeTest extends TestCase
{
    public function testInjectCanBeAppliedToPropertyAndParameter(): void
    {
        $prop = new \ReflectionProperty(InjectAttributeDummy::class, 'prop');
        $propAttrs = $prop->getAttributes(Inject::class);

        $ctor = new \ReflectionMethod(InjectAttributeDummy::class, '__construct');
        $paramAttrs = $ctor->getParameters()[0]->getAttributes(Inject::class);

        $this->assertCount(1, $propAttrs);
        $this->assertCount(1, $paramAttrs);
    }
}
