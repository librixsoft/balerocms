<?php

declare(strict_types=1);

namespace Tests\Framework\Attributes\Validation;

use Framework\Attributes\Validation\Email;
use PHPUnit\Framework\TestCase;

class EmailDummy2
{
    #[Email]
    public string $email = '';
}

final class EmailTest extends TestCase
{
    public function testEmailAttributeDefaultMessage(): void
    {
        $attr = (new \ReflectionProperty(EmailDummy2::class, 'email'))->getAttributes(Email::class)[0]->newInstance();
        $this->assertSame('Invalid email address', $attr->message);
    }
}
