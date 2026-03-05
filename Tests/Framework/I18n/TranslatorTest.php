<?php

declare(strict_types=1);

namespace Tests\Framework\I18n;

use Framework\I18n\LangManager;
use Framework\I18n\Translator;
use PHPUnit\Framework\TestCase;

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__, 3));
}

final class TranslatorTest extends TestCase
{
    public function testGetTextAliasParamsAndSetLang(): void
    {
        $_SESSION['lang'] = 'en';

        $lm = $this->createMock(LangManager::class);
        $lm->expects($this->once())->method('load');
        $lm->method('get')->willReturnMap([
            ['welcome.message', 'welcome.message', 'Hello {name}'],
            ['x.y', 'def', 'def'],
        ]);
        $lm->method('current')->willReturn('en');

        $t = new Translator($lm);

        $this->assertSame('Hello {name}', $t->getText('welcome.message'));
        $this->assertSame('def', $t->t('x.y', 'def'));
        $this->assertSame('Hello Ana', $t->transParams('welcome.message', ['name' => 'Ana']));
        $this->assertSame('en', $t->getCurrentLang());

        $lm2 = $this->createMock(LangManager::class);
        $lm2->expects($this->once())->method('setCurrentLang')->with('es');
        $lm2->expects($this->once())->method('load')->with('es', $this->stringContains('/resources/lang'));
        $t2 = new Translator($lm2);
        $t2->setLang('es');
        $this->assertSame('es', $_SESSION['lang']);
    }
}
