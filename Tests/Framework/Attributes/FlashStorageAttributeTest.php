<?php

declare(strict_types=1);

namespace Tests\Framework\Attributes;

use Framework\Attributes\FlashStorage;
use PHPUnit\Framework\TestCase;

class FlashStorageAttributeDummy
{
    #[FlashStorage]
    public string $prop = '';

    public function run(#[FlashStorage] string $value): string
    {
        return $value;
    }
}

final class FlashStorageAttributeTest extends TestCase
{
    public function testFlashStorageCanBeAppliedToPropertyAndParameter(): void
    {
        $prop = new \ReflectionProperty(FlashStorageAttributeDummy::class, 'prop');
        $propAttrs = $prop->getAttributes(FlashStorage::class);

        $method = new \ReflectionMethod(FlashStorageAttributeDummy::class, 'run');
        $paramAttrs = $method->getParameters()[0]->getAttributes(FlashStorage::class);

        $this->assertCount(1, $propAttrs);
        $this->assertCount(1, $paramAttrs);
    }
}
