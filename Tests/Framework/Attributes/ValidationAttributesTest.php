<?php

declare(strict_types=1);

namespace Tests\Framework\Attributes;

use Framework\Attributes\Validation\Email;
use Framework\Attributes\Validation\FieldMatch;
use Framework\Attributes\Validation\Max;
use Framework\Attributes\Validation\Min;
use Framework\Attributes\Validation\NotEmpty;
use Framework\Attributes\Validation\Pattern;
use PHPUnit\Framework\TestCase;

final class ValidationAttributesTest extends TestCase
{
    public function testConstructorsExposePublicProperties(): void
    {
        $this->assertSame('Field cannot be empty', (new NotEmpty())->message);
        $this->assertSame('Invalid email address', (new Email())->message);
        $fm = new FieldMatch('password');
        $this->assertSame('password', $fm->field);
        $this->assertSame(3, (new Min(3))->value);
        $this->assertSame(8, (new Max(8))->value);
        $this->assertSame('/a+/', (new Pattern('/a+/'))->regex);
    }
}
