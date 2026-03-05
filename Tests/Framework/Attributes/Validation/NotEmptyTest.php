<?php

declare(strict_types=1);

namespace Tests\Framework\Attributes\Validation;

use Framework\Attributes\Validation\NotEmpty;
use PHPUnit\Framework\TestCase;

class NotEmptyDummy
{
    #[NotEmpty]
    public string $title = '';
}

final class NotEmptyTest extends TestCase
{
    public function testNotEmptyDefaultMessage(): void
    {
        $attr = (new \ReflectionProperty(NotEmptyDummy::class, 'title'))
            ->getAttributes(NotEmpty::class)[0]
            ->newInstance();

        $this->assertSame('Field cannot be empty', $attr->message);
    }
}
