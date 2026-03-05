<?php

declare(strict_types=1);

namespace Tests\Framework\Attributes\Validation;

use Framework\Attributes\Validation\Min;
use PHPUnit\Framework\TestCase;

class MinDummy
{
    #[Min(3, 'too short')]
    public string $username = '';
}

final class MinTest extends TestCase
{
    public function testMinStoresValueAndCustomMessage(): void
    {
        $attr = (new \ReflectionProperty(MinDummy::class, 'username'))
            ->getAttributes(Min::class)[0]
            ->newInstance();

        $this->assertSame(3, $attr->value);
        $this->assertSame('too short', $attr->message);
    }
}
