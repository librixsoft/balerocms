<?php

declare(strict_types=1);

namespace Tests\Framework\Exceptions;

use Framework\Exceptions\RouterException;
use PHPUnit\Framework\TestCase;

final class RouterExceptionTest extends TestCase
{
    public function testRouterExceptionCarriesMessageAndCode(): void
    {
        $e = new RouterException('router error', 21);
        $this->assertSame('router error', $e->getMessage());
        $this->assertSame(21, $e->getCode());
    }
}
