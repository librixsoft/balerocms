<?php

declare(strict_types=1);

namespace Tests\Framework\I18n;

use Framework\I18n\LangManager;
use PHPUnit\Framework\TestCase;

final class LangManagerTest extends TestCase
{
    private string $tmp;

    protected function setUp(): void
    {
        $this->tmp = sys_get_temp_dir() . '/lang-test-' . uniqid();
        @mkdir($this->tmp . '/en', 0777, true);
        file_put_contents($this->tmp . '/en/messages.php', "<?php return ['hello' => 'Hello'];");
    }

    protected function tearDown(): void
    {
        @unlink($this->tmp . '/en/messages.php');
        @rmdir($this->tmp . '/en');
        @rmdir($this->tmp);
    }

    public function testLoadAndGetCurrentAndSetCurrent(): void
    {
        $lm = new LangManager();
        $lm->load('en', $this->tmp);

        $this->assertSame('Hello', $lm->get('messages.hello', 'x'));
        $this->assertSame('en', $lm->current());

        $lm->setCurrentLang('es');
        $this->assertSame('es', $lm->current());
        $this->assertSame('fallback', $lm->get('missing.key', 'fallback'));
    }
}
