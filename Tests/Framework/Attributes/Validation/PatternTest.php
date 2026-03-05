<?php

declare(strict_types=1);

namespace Tests\Framework\Attributes\Validation;

use Framework\Attributes\Validation\Pattern;
use PHPUnit\Framework\TestCase;

class PatternDummy
{
    #[Pattern('/^[a-z]+$/', 'letters only')]
    public string $slug = '';
}

final class PatternTest extends TestCase
{
    public function testPatternStoresRegexAndMessage(): void
    {
        $attr = (new \ReflectionProperty(PatternDummy::class, 'slug'))
            ->getAttributes(Pattern::class)[0]
            ->newInstance();

        $this->assertSame('/^[a-z]+$/', $attr->regex);
        $this->assertSame('letters only', $attr->message);
    }
}
