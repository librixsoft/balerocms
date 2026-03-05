<?php

declare(strict_types=1);

namespace Tests\Framework\Exceptions;

use Framework\Exceptions\BootException;
use Framework\Exceptions\DTOCacheException;
use PHPUnit\Framework\TestCase;

final class DTOCacheExceptionTest extends TestCase
{
    public function testDtoCacheExceptionExtendsBootException(): void
    {
        $e = new DTOCacheException('dto cache fail');

        $this->assertInstanceOf(BootException::class, $e);
        $this->assertSame('dto cache fail', $e->getMessage());
    }
}
