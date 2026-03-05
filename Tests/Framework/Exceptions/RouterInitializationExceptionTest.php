<?php

declare(strict_types=1);

namespace Tests\Framework\Exceptions;

use Framework\Exceptions\BootException;
use Framework\Exceptions\RouterInitializationException;
use PHPUnit\Framework\TestCase;

final class RouterInitializationExceptionTest extends TestCase
{
    public function testRouterInitializationExceptionExtendsBootException(): void
    {
        $e = new RouterInitializationException('router init fail');

        $this->assertInstanceOf(BootException::class, $e);
        $this->assertSame('router init fail', $e->getMessage());
    }
}
