<?php

declare(strict_types=1);

namespace Tests\Framework\Preview;

use Framework\Preview\PreviewGenerator;
use PHPUnit\Framework\TestCase;

final class PreviewGeneratorTest extends TestCase
{
    private string $tmpRoot;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tmpRoot = sys_get_temp_dir() . '/balerocms-preview-' . uniqid();
        mkdir($this->tmpRoot . '/assets/images', 0777, true);
        mkdir($this->tmpRoot . '/assets/fonts', 0777, true);
        $_SERVER['DOCUMENT_ROOT'] = $this->tmpRoot;
    }

    protected function tearDown(): void
    {
        $this->deleteDir($this->tmpRoot);
        parent::tearDown();
    }

    private function deleteDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $items = scandir($dir);
        if ($items === false) {
            return;
        }
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . '/' . $item;
            if (is_dir($path)) {
                $this->deleteDir($path);
            } else {
                unlink($path);
            }
        }
        rmdir($dir);
    }
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

    public function testRenderUsesStaticFallbackWhenTitleMissing(): void
    {
        $generator = new PreviewGenerator();

        ob_start();
        $generator->render(null);
        $output = ob_get_clean();

        $this->assertSame('', $output);
    }

    public function testRenderOutputsStaticFallbackWhenImageExists(): void
    {
        $imagePath = $this->tmpRoot . '/assets/images/og-image.png';
        file_put_contents($imagePath, 'png-data');

        $generator = new PreviewGenerator();

        ob_start();
        $generator->render('');
        $output = ob_get_clean();

        $this->assertSame('png-data', $output);
    }

    public function testRenderOutputsPngWhenNoTtfSupport(): void
    {
        $generator = new class() extends PreviewGenerator {
            protected function hasGdSupport(): bool
            {
                return true;
            }

            protected function createImage(int $width, int $height)
            {
                return 'img';
            }

            protected function allocateColor($image, int $red, int $green, int $blue): int
            {
                return 1;
            }

            protected function fillBackground($image, int $width, int $height, int $color): void
            {
                // no-op
            }

            protected function drawSimpleText($image, int $width, int $height, string $title, int $color): void
            {
                // no-op
            }

            protected function output($image): void
            {
                echo "\x89PNGstub";
            }
        };

        ob_start();
        $generator->render('Hola');
        $output = ob_get_clean();

        $this->assertNotSame('', $output);
        $this->assertStringStartsWith("\x89PNG", $output);
    }

    public function testRenderUsesTtfPathWhenAvailable(): void
    {
        $generator = new class() extends PreviewGenerator {
            protected function hasGdSupport(): bool
            {
                return true;
            }

            protected function createImage(int $width, int $height)
            {
                return 'img';
            }

            protected function allocateColor($image, int $red, int $green, int $blue): int
            {
                return 1;
            }

            protected function fillBackground($image, int $width, int $height, int $color): void
            {
                // no-op
            }

            protected function hasTtfSupport(string $fontPath): bool
            {
                return true;
            }

            protected function getTextBoundingBox(int $fontSize, string $fontPath, string $title): array
            {
                return [0, 0, 100, 0, 100, -20, 0, -20];
            }

            protected function drawTtfText($image, int $fontSize, int $x, int $y, int $color, string $fontPath, string $title): void
            {
                // no-op for test
            }

            protected function output($image): void
            {
                echo "\x89PNGstub";
            }
        };

        ob_start();
        $generator->render('Titulo');
        $output = ob_get_clean();

        $this->assertNotSame('', $output);
        $this->assertStringStartsWith("\x89PNG", $output);
    }
}
