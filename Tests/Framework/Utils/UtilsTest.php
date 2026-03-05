<?php

declare(strict_types=1);

namespace Tests\Framework\Utils;

use Framework\Utils\Utils;
use PHPUnit\Framework\TestCase;

final class UtilsTest extends TestCase
{
    public function testSlugifyNormalizesText(): void
    {
        $utils = new Utils();
        $this->assertSame('hola-mundo-2026', $utils->slugify('¡Hola Mundo! 2026'));
    }

    public function testSlugifyReturnsFallbackWhenEmpty(): void
    {
        $utils = new Utils();
        $this->assertSame('page', $utils->slugify('@@@###'));
    }
}
