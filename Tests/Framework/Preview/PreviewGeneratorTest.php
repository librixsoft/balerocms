<?php

declare(strict_types=1);

namespace Tests\Framework\Preview;

use Framework\Preview\PreviewGenerator;
use PHPUnit\Framework\TestCase;

final class PreviewGeneratorTest extends TestCase
{
    public function testGeneratePreviewUrlUsesAbsoluteOgImageWhenPresent(): void
    {
        $generator = new PreviewGenerator();

        $url = $generator->generatePreviewUrl([
            'url' => 'https://example.com/page',
            'og_image' => 'https://cdn.example.com/preview.png',
        ]);

        $this->assertMatchesRegularExpression(
            '#^https://cdn\.example\.com/preview\.png\?v=\d{14}$#',
            $url
        );
    }

    public function testGeneratePreviewUrlUsesRelativeOgImageWithBaseUrl(): void
    {
        $generator = new PreviewGenerator();

        $url = $generator->generatePreviewUrl([
            'url' => 'https://example.com/blog/post',
            'og_image' => '/images/cover.png',
        ]);

        $this->assertMatchesRegularExpression(
            '#^https://example\.com/images/cover\.png\?v=\d{14}$#',
            $url
        );
    }

    public function testGeneratePreviewUrlBuildsPageOgWhenSlugExists(): void
    {
        $generator = new PreviewGenerator();

        $url = $generator->generatePreviewUrl([
            'url' => 'https://example.com',
            'page' => ['static_url' => 'mi página'],
        ]);

        $this->assertMatchesRegularExpression(
            '#^https://example\.com/page/og/mi\+p%C3%A1gina\?v=\d{14}$#',
            $url
        );
    }

    public function testGeneratePreviewUrlFallsBackToGenericTitleWhenNoOgImageOrSlug(): void
    {
        $generator = new PreviewGenerator();

        $url = $generator->generatePreviewUrl([
            'url' => 'https://example.com',
            'title' => 'Hola mundo',
        ]);

        $this->assertMatchesRegularExpression(
            '#^https://example\.com/page/og/generic\?title=Hola\+mundo&v=\d{14}$#',
            $url
        );
    }

    public function testGeneratePreviewUrlUsesObjectPageSlugAndPreservesExistingQueryString(): void
    {
        $generator = new PreviewGenerator();

        $url = $generator->generatePreviewUrl([
            'url' => 'https://example.com',
            'og_image' => 'https://cdn.example.com/image.png?size=large',
            'page' => (object) ['static_url' => 'ignored-because-og-image-wins'],
        ]);

        $this->assertMatchesRegularExpression(
            '#^https://cdn\.example\.com/image\.png\?size=large&v=\d{14}$#',
            $url
        );
    }
}
