<?php

declare(strict_types=1);

namespace Tests\Framework\Exceptions;

use Framework\Exceptions\ContainerException;
use PHPUnit\Framework\TestCase;

final class ContainerExceptionTest extends TestCase
{
    public function testContainerExceptionCarriesMessageAndCode(): void
    {
        $e = new ContainerException('container error', 11);

        $this->assertSame('container error', $e->getMessage());
        $this->assertSame(11, $e->getCode());
    }
}
