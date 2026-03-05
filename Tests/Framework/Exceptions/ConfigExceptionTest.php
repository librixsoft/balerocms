<?php

declare(strict_types=1);

namespace Tests\Framework\Exceptions;

use Framework\Exceptions\ConfigException;
use PHPUnit\Framework\TestCase;

final class ConfigExceptionTest extends TestCase
{
    public function testConfigExceptionCarriesMessageAndCode(): void
    {
        $e = new ConfigException('cfg error', 10);

        $this->assertSame('cfg error', $e->getMessage());
        $this->assertSame(10, $e->getCode());
    }
}
