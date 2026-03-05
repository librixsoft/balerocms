<?php

declare(strict_types=1);

namespace Tests\Framework\Http;

use Framework\Http\Auth;
use PHPUnit\Framework\TestCase;

class AuthAttributeDummyController
{
    #[Auth]
    public function secured(): void {}

    #[Auth(false)]
    public function public(): void {}
}

final class AuthAttributeTest extends TestCase
{
    public function testAuthDefaultRequiredIsTrue(): void
    {
        $auth = new Auth();
        $this->assertTrue($auth->required);
    }

    public function testAuthCanBeAppliedToMethodsWithCustomFlag(): void
    {
        $m1 = new \ReflectionMethod(AuthAttributeDummyController::class, 'secured');
        $a1 = $m1->getAttributes(Auth::class)[0]->newInstance();

        $m2 = new \ReflectionMethod(AuthAttributeDummyController::class, 'public');
        $a2 = $m2->getAttributes(Auth::class)[0]->newInstance();

        $this->assertTrue($a1->required);
        $this->assertFalse($a2->required);
    }
}
