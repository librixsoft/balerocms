<?php

declare(strict_types=1);

namespace Tests\Framework\Attributes;

use Framework\Attributes\FlashStorage;
use PHPUnit\Framework\TestCase;

class FlashStorageDummy
{
    #[FlashStorage]
    public string $value = '';
}

final class FlashStorageTest extends TestCase
{
    public function testFlashStorageAttributeApplied(): void
    {
        $attrs = (new \ReflectionProperty(FlashStorageDummy::class, 'value'))->getAttributes(FlashStorage::class);
        $this->assertCount(1, $attrs);
    }
}
