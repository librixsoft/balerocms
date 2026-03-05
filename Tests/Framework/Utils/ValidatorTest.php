<?php

declare(strict_types=1);

namespace Tests\Framework\Utils;

use Framework\Attributes\Validation\Email;
use Framework\Attributes\Validation\FieldMatch;
use Framework\Attributes\Validation\Max;
use Framework\Attributes\Validation\Min;
use Framework\Attributes\Validation\NotEmpty;
use Framework\Attributes\Validation\Pattern;
use Framework\Utils\Validator;
use PHPUnit\Framework\TestCase;

final class ValidatorTest extends TestCase
{
    public function testValidatePassesWithValidData(): void
    {
        $dto = new class {
            #[NotEmpty]
            public string $name = 'Ana';

            #[Email]
            public string $email = 'ana@example.com';

            #[Min(3)]
            #[Max(10)]
            #[Pattern('/^[a-zA-Z0-9_]+$/')]
            public string $username = 'ana_01';

            public string $password = 'secret';

            #[FieldMatch('password')]
            public string $confirm = 'secret';
        };

        $validator = new Validator();
        $this->assertTrue($validator->validate($dto));
        $this->assertFalse($validator->fails());
        $this->assertSame([], $validator->errors());
    }

    public function testValidateCollectsExpectedErrors(): void
    {
        $dto = new class {
            #[NotEmpty('name required')]
            public string $name = '';

            #[Email('bad email')]
            public string $email = 'not-an-email';

            #[Min(5, 'too short')]
            #[Max(6, 'too long')]
            #[Pattern('/^[0-9]+$/', 'numbers only')]
            public string $code = 'ab';

            public string $password = 'secret';

            #[FieldMatch('password', 'no match')]
            public string $confirm = 'different';
        };

        $validator = new Validator();
        $this->assertFalse($validator->validate($dto));
        $this->assertTrue($validator->fails());

        $errors = $validator->errors();
        $this->assertSame('name required', $errors['name']);
        $this->assertSame('bad email', $errors['email']);
        // Last failing rule for code overwrites prior one in current implementation
        $this->assertSame('numbers only', $errors['code']);
        $this->assertSame('no match', $errors['confirm']);
    }

    public function testNotEmptyTreatsStringZeroAsValid(): void
    {
        $dto = new class {
            #[NotEmpty]
            public string $value = '0';
        };

        $validator = new Validator();
        $this->assertTrue($validator->validate($dto));
    }
}
