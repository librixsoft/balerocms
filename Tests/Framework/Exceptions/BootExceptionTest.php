<?php

declare(strict_types=1);

namespace Tests\Framework\Exceptions;

use Framework\Exceptions\BootException;
use PHPUnit\Framework\TestCase;

final class BootExceptionTest extends TestCase
{
    public function testBootExceptionIsThrowableWithMessageAndCode(): void
    {
        $e = new BootException('boot error', 500);

        $this->assertSame('boot error', $e->getMessage());
        $this->assertSame(500, $e->getCode());
    }
}
