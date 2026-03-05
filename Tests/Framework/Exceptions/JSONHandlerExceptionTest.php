<?php

declare(strict_types=1);

namespace Tests\Framework\Exceptions;

use Framework\Exceptions\JSONHandlerException;
use PHPUnit\Framework\TestCase;

final class JSONHandlerExceptionTest extends TestCase
{
    public function testJsonHandlerExceptionCarriesMessageAndCode(): void
    {
        $e = new JSONHandlerException('json error', 13);

        $this->assertSame('json error', $e->getMessage());
        $this->assertSame(13, $e->getCode());
    }
}
