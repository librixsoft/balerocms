<?php

declare(strict_types=1);

namespace Tests\Framework\Rendering;

use Framework\I18n\LangManager;
use Framework\Rendering\ProcessorKeyPath;
use PHPUnit\Framework\TestCase;

final class ProcessorKeyPathTest extends TestCase
{
    public function testProcessUsesFlatParamsFirstThenLangManager(): void
    {
        $lm = $this->createMock(LangManager::class);
        $lm->method('get')->willReturnMap([
            ['site.title', '{site.title}', 'Balero'],
            ['site.footer', '{site.footer}', 'Footer'],
        ]);

        $p = new ProcessorKeyPath($lm);
        $out = $p->process('{site.title} - {site.footer}', ['site.title' => 'LocalTitle']);

        $this->assertSame('LocalTitle - Footer', $out);
    }
}
