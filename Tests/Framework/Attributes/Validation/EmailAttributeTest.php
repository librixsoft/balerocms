<?php

declare(strict_types=1);

namespace Tests\Framework\Attributes\Validation;

use Framework\Attributes\Validation\Email;
use PHPUnit\Framework\TestCase;

class EmailAttributeDummy
{
    #[Email]
    public string $email = '';

    #[Email('Correo inválido')]
    public string $secondary = '';
}

final class EmailAttributeTest extends TestCase
{
    public function testEmailAttributeDefaultAndCustomMessage(): void
    {
        $r = new \ReflectionClass(EmailAttributeDummy::class);

        $a1 = $r->getProperty('email')->getAttributes(Email::class)[0]->newInstance();
        $a2 = $r->getProperty('secondary')->getAttributes(Email::class)[0]->newInstance();

        $this->assertSame('Invalid email address', $a1->message);
        $this->assertSame('Correo inválido', $a2->message);
    }
}
