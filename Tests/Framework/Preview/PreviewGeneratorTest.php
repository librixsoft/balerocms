<?php

declare(strict_types=1);

namespace Tests\Framework\Preview;

use Framework\Preview\PreviewGenerator;
use PHPUnit\Framework\TestCase;

final class PreviewGeneratorTest extends TestCase
{
    public function testGeneratePreviewUrlWithOgImageSlugAndFallback(): void
    {
        $g = new PreviewGenerator();

        $u1 = $g->generatePreviewUrl(['url' => 'https://example.com', 'og_image' => 'https://cdn/img.png']);
        $this->assertStringStartsWith('https://cdn/img.png?v=', $u1);

        $u2 = $g->generatePreviewUrl(['url' => 'https://example.com', 'page' => ['static_url' => 'hola mundo']]);
        $this->assertStringContainsString('/page/og/hola+mundo?v=', $u2);

        $u3 = $g->generatePreviewUrl(['url' => 'https://example.com', 'title' => 'Mi titulo']);
        $this->assertStringContainsString('/page/og/generic?title=Mi+titulo&v=', $u3);
    }
}
