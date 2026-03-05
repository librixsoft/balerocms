<?php

declare(strict_types=1);

namespace Tests\Framework\Attributes\Validation;

use Framework\Attributes\Validation\FieldMatch;
use PHPUnit\Framework\TestCase;

class FieldMatchDummy
{
    #[FieldMatch('password')]
    public string $confirm = '';
}

final class FieldMatchTest extends TestCase
{
    public function testFieldMatchStoresFieldAndDefaultMessage(): void
    {
        $attr = (new \ReflectionProperty(FieldMatchDummy::class, 'confirm'))
            ->getAttributes(FieldMatch::class)[0]
            ->newInstance();

        $this->assertSame('password', $attr->field);
        $this->assertSame('Fields do not match', $attr->message);
    }
}
