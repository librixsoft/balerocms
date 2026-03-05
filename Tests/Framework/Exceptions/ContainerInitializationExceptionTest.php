<?php

declare(strict_types=1);

namespace Tests\Framework\Exceptions;

use Framework\Exceptions\BootException;
use Framework\Exceptions\ContainerInitializationException;
use PHPUnit\Framework\TestCase;

final class ContainerInitializationExceptionTest extends TestCase
{
    public function testContainerInitializationExceptionExtendsBootException(): void
    {
        $e = new ContainerInitializationException('container init fail');

        $this->assertInstanceOf(BootException::class, $e);
        $this->assertSame('container init fail', $e->getMessage());
    }
}
