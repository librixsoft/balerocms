<?php

declare(strict_types=1);

namespace Tests\Framework\Exceptions;

use Framework\Exceptions\MysqlException;
use PHPUnit\Framework\TestCase;

final class MysqlExceptionTest extends TestCase
{
    public function testMysqlExceptionCarriesMessageAndCode(): void
    {
        $e = new MysqlException('mysql error', 20);
        $this->assertSame('mysql error', $e->getMessage());
        $this->assertSame(20, $e->getCode());
    }
}
