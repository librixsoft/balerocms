<?php

declare(strict_types=1);

namespace Tests\Framework\Exceptions;

use Framework\Exceptions\ModelException;
use PHPUnit\Framework\TestCase;

final class ModelExceptionTest extends TestCase
{
    public function testModelExceptionCarriesMessageAndCode(): void
    {
        $e = new ModelException('model error', 14);

        $this->assertSame('model error', $e->getMessage());
        $this->assertSame(14, $e->getCode());
    }
}
