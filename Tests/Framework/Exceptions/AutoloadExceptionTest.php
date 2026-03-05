<?php

declare(strict_types=1);

namespace Tests\Framework\Exceptions;

use Framework\Exceptions\AutoloadException;
use Framework\Exceptions\BootException;
use PHPUnit\Framework\TestCase;

final class AutoloadExceptionTest extends TestCase
{
    public function testAutoloadExceptionExtendsBootException(): void
    {
        $e = new AutoloadException('autoload fail');

        $this->assertInstanceOf(BootException::class, $e);
        $this->assertSame('autoload fail', $e->getMessage());
    }
}
