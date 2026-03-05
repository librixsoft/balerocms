<?php

declare(strict_types=1);

namespace Tests\Framework\Core;

use Framework\Core\EarlyErrorConsole;
use PHPUnit\Framework\TestCase;

final class EarlyErrorConsoleTest extends TestCase
{
    public function testEarlyErrorConsoleClassHasRenderMethod(): void
    {
        $r = new \ReflectionClass(EarlyErrorConsole::class);

        $this->assertTrue($r->hasMethod('render'));
        $this->assertTrue($r->hasMethod('getCss'));
    }
}
