<?php

declare(strict_types=1);

namespace Tests\Framework\DI;

use Framework\DI\Context;
use PHPUnit\Framework\TestCase;

final class ContextTest extends TestCase
{
    public function testContextClassShapeAndGetMethod(): void
    {
        $r = new \ReflectionClass(Context::class);

        $this->assertTrue($r->hasMethod('__construct'));
        $this->assertTrue($r->hasMethod('get'));
    }
}
