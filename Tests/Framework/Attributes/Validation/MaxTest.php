<?php

declare(strict_types=1);

namespace Tests\Framework\Attributes\Validation;

use Framework\Attributes\Validation\Max;
use PHPUnit\Framework\TestCase;

class MaxDummy
{
    #[Max(12)]
    public string $name = '';
}

final class MaxTest extends TestCase
{
    public function testMaxStoresValueAndDefaultMessage(): void
    {
        $attr = (new \ReflectionProperty(MaxDummy::class, 'name'))
            ->getAttributes(Max::class)[0]
            ->newInstance();

        $this->assertSame(12, $attr->value);
        $this->assertSame('Value is too long', $attr->message);
    }
}
