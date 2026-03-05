<?php

declare(strict_types=1);

namespace Tests\Framework\I18n;

use Framework\Http\RequestHelper;
use Framework\I18n\LangManager;
use Framework\I18n\LangSelector;
use PHPUnit\Framework\TestCase;

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__, 3));
}

final class LangSelectorTest extends TestCase
{
    public function testGetLanguageParamsFromGetAndFallback(): void
    {
        $_SESSION = [];
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'fr-FR';

        $tmp = sys_get_temp_dir() . '/lang-selector-' . uniqid();
        @mkdir($tmp . '/es', 0777, true);
        file_put_contents($tmp . '/es/messages.php', "<?php return ['k' => 'v'];");

        $lm = new LangManager();

        $rh = $this->createMock(RequestHelper::class);
        $rh->method('hasGet')->with('lang')->willReturn(true);
        $rh->method('get')->with('lang')->willReturn('es');

        $selector = new LangSelector($lm, $rh);
        $selector->setLangPath($tmp);

        $result = $selector->getLanguageParams();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('messages.k', $result);

        $selector->setLangPath('/tmp/langs');
        $this->assertSame('/tmp/langs', $selector->getLangPath());

        @unlink($tmp . '/es/messages.php');
        @rmdir($tmp . '/es');
        @rmdir($tmp);
    }

    public function testGetLanguageParamsFallsBackToSessionAndSupportedLanguage(): void
    {
        $_SESSION = ['lang' => 'es'];

        $tmp = sys_get_temp_dir() . '/lang-selector-session-' . uniqid();
        @mkdir($tmp . '/es', 0777, true);
        file_put_contents($tmp . '/es/messages.php', "<?php return ['hello' => 'hola'];");

        $lm = new LangManager();
        $rh = $this->createMock(RequestHelper::class);
        $rh->method('hasGet')->with('lang')->willReturn(false);

        $selector = new LangSelector($lm, $rh);
        $selector->setLangPath($tmp);

        $result = $selector->getLanguageParams();

        $this->assertSame('es', $_SESSION['lang']);
        $this->assertSame('hola', $result['messages.hello']);

        @unlink($tmp . '/es/messages.php');
        @rmdir($tmp . '/es');
        @rmdir($tmp);
    }

    public function testUnsupportedLanguageFallsBackToEnglish(): void
    {
        $_SESSION = [];
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'de-DE';

        $tmp = sys_get_temp_dir() . '/lang-selector-en-' . uniqid();
        @mkdir($tmp . '/en', 0777, true);
        file_put_contents($tmp . '/en/messages.php', "<?php return ['x' => 'y'];");

        $lm = new LangManager();
        $rh = $this->createMock(RequestHelper::class);
        $rh->method('hasGet')->with('lang')->willReturn(false);

        $selector = new LangSelector($lm, $rh);
        $selector->setLangPath($tmp);

        $result = $selector->getLanguageParams();

        $this->assertSame('en', $_SESSION['lang']);
        $this->assertSame('y', $result['messages.x']);

        @unlink($tmp . '/en/messages.php');
        @rmdir($tmp . '/en');
        @rmdir($tmp);
    }
}
