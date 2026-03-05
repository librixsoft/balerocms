<?php

declare(strict_types=1);

namespace Tests\Framework\Config;

use Framework\Config\ViewConfig;
use PHPUnit\Framework\TestCase;

final class ViewConfigTest extends TestCase
{
    public function testConstructorStoresValuesAndCustomExtensions(): void
    {
        $cfg = new ViewConfig('/views', '/lang', ['html', 'twig']);

        $this->assertSame('/views', $cfg->viewsPath);
        $this->assertSame('/lang', $cfg->langBasePath);
        $this->assertSame(['html', 'twig'], $cfg->allowedExtensions);
    }

    public function testDefaultAllowedExtensionsIsHtml(): void
    {
        $cfg = new ViewConfig('/views', '/lang');

        $this->assertSame(['html'], $cfg->allowedExtensions);
    }
}
