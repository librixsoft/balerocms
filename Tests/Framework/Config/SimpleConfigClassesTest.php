<?php

declare(strict_types=1);

namespace Tests\Framework\Config;

use Framework\Config\SetupConfig;
use Framework\Config\ViewConfig;
use PHPUnit\Framework\TestCase;

final class SimpleConfigClassesTest extends TestCase
{
    public function testSetupConfigStoresPath(): void
    {
        $cfg = new SetupConfig('/tmp/config.json');
        $this->assertSame('/tmp/config.json', $cfg->configPath);
    }

    public function testViewConfigStoresConstructorValuesAndDefaults(): void
    {
        $cfgDefault = new ViewConfig('/views', '/lang');
        $this->assertSame(['html'], $cfgDefault->allowedExtensions);

        $cfg = new ViewConfig('/views', '/lang', ['html', 'twig']);
        $this->assertSame('/views', $cfg->viewsPath);
        $this->assertSame('/lang', $cfg->langBasePath);
        $this->assertSame(['html', 'twig'], $cfg->allowedExtensions);
    }
}
