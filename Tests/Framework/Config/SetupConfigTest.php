<?php

declare(strict_types=1);

namespace Tests\Framework\Config;

use Framework\Config\SetupConfig;
use PHPUnit\Framework\TestCase;

final class SetupConfigTest extends TestCase
{
    public function testConstructorStoresConfigPath(): void
    {
        $cfg = new SetupConfig('/tmp/balero.config.json');

        $this->assertSame('/tmp/balero.config.json', $cfg->configPath);
    }
}
