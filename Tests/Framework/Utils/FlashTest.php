<?php

declare(strict_types=1);

namespace Tests\Framework\Utils;

use Framework\Utils\Flash;
use PHPUnit\Framework\TestCase;

final class FlashTest extends TestCase
{
    protected function setUp(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_unset();
        }
        $_SESSION = [];
    }

    public function testSetHasGetAndAutoDelete(): void
    {
        $flash = new Flash();
        $flash->set('ok', 'saved');

        $this->assertTrue($flash->has('ok'));
        $this->assertSame('saved', $flash->get('ok'));
        $this->assertFalse($flash->has('ok'));
        $this->assertSame('default', $flash->get('ok', 'default'));
    }

    public function testAllClearAndDelete(): void
    {
        $flash = new Flash();
        $flash->set('a', 1);
        $flash->set('b', 2);

        $all = $flash->all();
        $this->assertSame(['a' => 1, 'b' => 2], $all);

        $flash->set('temp', 'x');
        $flash->delete('temp');
        $this->assertFalse($flash->has('temp'));

        $flash->set('persist', 'y');
        $flash->clear();
        $this->assertSame([], $flash->all());
    }
}
