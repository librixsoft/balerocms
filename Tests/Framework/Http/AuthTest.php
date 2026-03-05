<?php

declare(strict_types=1);

namespace Tests\Framework\Http;

use Framework\Http\Auth;
use PHPUnit\Framework\TestCase;

class AuthDummy2
{
    #[Auth(false)]
    public function run(): void {}
}

final class AuthTest extends TestCase
{
    public function testAuthRequiredFlag(): void
    {
        $attr = (new \ReflectionMethod(AuthDummy2::class, 'run'))->getAttributes(Auth::class)[0]->newInstance();
        $this->assertFalse($attr->required);
        $this->assertTrue((new Auth())->required);
    }
}
