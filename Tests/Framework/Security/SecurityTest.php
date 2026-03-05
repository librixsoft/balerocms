<?php

declare(strict_types=1);

namespace Tests\Framework\Security;

use Framework\Security\Security;
use PHPUnit\Framework\TestCase;

final class SecurityTest extends TestCase
{
    public function testAntiXssSanitizesDangerousContent(): void
    {
        $security = new Security();
        $html = "<script>alert(1)</script><a href=\"javascript:alert(1)\" onclick=\"x()\">ok</a>\x07";

        $clean = $security->antiXSS($html);

        $this->assertStringNotContainsString('<script>', $clean);
        $this->assertStringNotContainsString('onclick=', $clean);
        $this->assertStringContainsString('href="#"', $clean);
    }

    public function testSanitizeAndToIntAndToString(): void
    {
        $security = new Security();
        $this->assertSame('&lt;b&gt;x&lt;/b&gt;', $security->sanitize('<b>x</b>'));
        $this->assertSame(123, $security->toInt('1a2b3c'));
        $this->assertSame('123', (string)$security);
    }
}
