<?php

declare(strict_types=1);

namespace Tests\Framework\Exceptions;

use Framework\Exceptions\ControllerException;
use PHPUnit\Framework\TestCase;

final class ControllerExceptionTest extends TestCase
{
    public function testControllerExceptionCarriesMessageAndCode(): void
    {
        $e = new ControllerException('controller error', 12);

        $this->assertSame('controller error', $e->getMessage());
        $this->assertSame(12, $e->getCode());
    }
}
