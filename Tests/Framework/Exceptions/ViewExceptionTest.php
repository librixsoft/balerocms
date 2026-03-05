<?php

declare(strict_types=1);

namespace Tests\Framework\Exceptions;

use Framework\Exceptions\ViewException;
use PHPUnit\Framework\TestCase;

final class ViewExceptionTest extends TestCase
{
    public function testViewExceptionCarriesMessageAndCode(): void
    {
        $e = new ViewException('view error', 23);
        $this->assertSame('view error', $e->getMessage());
        $this->assertSame(23, $e->getCode());
    }
}
