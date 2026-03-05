<?php

declare(strict_types=1);

namespace Tests\Framework\Exceptions;

use Framework\Exceptions\UploaderException;
use PHPUnit\Framework\TestCase;

final class UploaderExceptionTest extends TestCase
{
    public function testUploaderExceptionCarriesMessageAndCode(): void
    {
        $e = new UploaderException('uploader error', 22);
        $this->assertSame('uploader error', $e->getMessage());
        $this->assertSame(22, $e->getCode());
    }
}
