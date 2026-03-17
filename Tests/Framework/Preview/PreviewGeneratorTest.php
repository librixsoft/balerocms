<?php

declare(strict_types=1);

namespace Tests\Framework\Preview;

use Framework\Preview\GdAdapterInterface;
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

    // ─── generatePreviewUrl ──────────────────────────────────────────────────────

    /**
     * og_image absoluta (empieza con http) agrega ?v= con cachebuster.
     */
    public function testGeneratePreviewUrlUsesAbsoluteOgImageWhenPresent(): void
    {
        $generator = new PreviewGenerator();

        $url = $generator->generatePreviewUrl([
            'url'      => 'https://example.com/page',
            'og_image' => 'https://cdn.example.com/preview.png',
        ]);

        $this->assertMatchesRegularExpression(
            '#^https://cdn\.example\.com/preview\.png\?v=\d{14}$#',
            $url
        );
    }

    /**
     * og_image relativa → se antepone baseUrl.
     */
    public function testGeneratePreviewUrlUsesRelativeOgImageWithBaseUrl(): void
    {
        $generator = new PreviewGenerator();

        $url = $generator->generatePreviewUrl([
            'url'      => 'https://example.com/blog/post',
            'og_image' => '/images/cover.png',
        ]);

        $this->assertMatchesRegularExpression(
            '#^https://example\.com/images/cover\.png\?v=\d{14}$#',
            $url
        );
    }

    /**
     * og_image absoluta que ya tiene query string → se usa & en lugar de ?.
     */
    public function testGeneratePreviewUrlUsesAmpersandWhenOgImageHasQueryString(): void
    {
        $generator = new PreviewGenerator();

        $url = $generator->generatePreviewUrl([
            'url'      => 'https://example.com',
            'og_image' => 'https://cdn.example.com/image.png?size=large',
        ]);

        $this->assertMatchesRegularExpression(
            '#^https://cdn\.example\.com/image\.png\?size=large&v=\d{14}$#',
            $url
        );
    }

    /**
     * og_image absoluta gana sobre la página (slug ignorado).
     */
    public function testGeneratePreviewUrlUsesObjectPageSlugAndPreservesExistingQueryString(): void
    {
        $generator = new PreviewGenerator();

        $url = $generator->generatePreviewUrl([
            'url'      => 'https://example.com',
            'og_image' => 'https://cdn.example.com/image.png?size=large',
            'page'     => (object) ['static_url' => 'ignored-because-og-image-wins'],
        ]);

        $this->assertMatchesRegularExpression(
            '#^https://cdn\.example\.com/image\.png\?size=large&v=\d{14}$#',
            $url
        );
    }

    /**
     * page como ARRAY con static_url genera la URL /page/og/{slug}.
     */
    public function testGeneratePreviewUrlBuildsPageOgWhenSlugExists(): void
    {
        $generator = new PreviewGenerator();

        $url = $generator->generatePreviewUrl([
            'url'  => 'https://example.com',
            'page' => ['static_url' => 'mi página'],
        ]);

        $this->assertMatchesRegularExpression(
            '#^https://example\.com/page/og/mi\+p%C3%A1gina\?v=\d{14}$#',
            $url
        );
    }

    /**
     * page como OBJETO con static_url genera la URL /page/og/{slug}.
     */
    public function testGeneratePreviewUrlBuildsPageOgWhenSlugExistsAsObject(): void
    {
        $generator = new PreviewGenerator();

        $page = new \stdClass();
        $page->static_url = 'my-page';

        $url = $generator->generatePreviewUrl([
            'url'  => 'https://example.com',
            'page' => $page,
        ]);

        $this->assertMatchesRegularExpression(
            '#^https://example\.com/page/og/my-page\?v=\d{14}$#',
            $url
        );
    }

    /**
     * page como objeto SIN static_url → cae al fallback genérico.
     */
    public function testGeneratePreviewUrlFallsBackWhenObjectPageHasNoSlug(): void
    {
        $generator = new PreviewGenerator();

        $url = $generator->generatePreviewUrl([
            'url'   => 'https://example.com',
            'page'  => new \stdClass(),
            'title' => 'Sin slug',
        ]);

        $this->assertMatchesRegularExpression(
            '#^https://example\.com/page/og/generic\?title=Sin\+slug&v=\d{14}$#',
            $url
        );
    }

    /**
     * Fallback genérico con título personalizado.
     */
    public function testGeneratePreviewUrlFallsBackToGenericTitleWhenNoOgImageOrSlug(): void
    {
        $generator = new PreviewGenerator();

        $url = $generator->generatePreviewUrl([
            'url'   => 'https://example.com',
            'title' => 'Hola mundo',
        ]);

        $this->assertMatchesRegularExpression(
            '#^https://example\.com/page/og/generic\?title=Hola\+mundo&v=\d{14}$#',
            $url
        );
    }

    /**
     * Fallback genérico SIN título → usa 'Preview' por defecto.
     */
    public function testGeneratePreviewUrlFallsBackToDefaultTitleWhenNoTitleProvided(): void
    {
        $generator = new PreviewGenerator();

        $url = $generator->generatePreviewUrl([
            'url' => 'https://example.com',
        ]);

        $this->assertMatchesRegularExpression(
            '#^https://example\.com/page/og/generic\?title=Preview&v=\d{14}$#',
            $url
        );
    }

    /**
     * URL vacía → base usa http:// con host vacío.
     */
    public function testGeneratePreviewUrlHandlesEmptyBaseUrl(): void
    {
        $generator = new PreviewGenerator();

        $url = $generator->generatePreviewUrl([]);

        $this->assertMatchesRegularExpression(
            '#^http:///page/og/generic\?title=Preview&v=\d{14}$#',
            $url
        );
    }

    // ─── render ─────────────────────────────────────────────────────────────────

    /**
     * render(null) → serveStaticFallback() sin imagen → output vacío (404 sin body).
     */
    public function testRenderUsesStaticFallbackWhenTitleMissing(): void
    {
        $generator = new PreviewGenerator();

        ob_start();
        $generator->render(null);
        $output = ob_get_clean();

        $this->assertSame('', $output);
    }

    /**
     * render('') → serveStaticFallback() con imagen existente → cuerpo del PNG.
     */
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

    /**
     * render() con título y GD NO disponible → serveStaticFallback().
     */
    public function testRenderUsesStaticFallbackWhenGdNotAvailable(): void
    {
        $imagePath = $this->tmpRoot . '/assets/images/og-image.png';
        file_put_contents($imagePath, 'fallback-png');

        $gd = $this->createMock(GdAdapterInterface::class);
        $gd->method('hasGdSupport')->willReturn(false);
        $gd->expects($this->never())->method('createImage');
        $gd->expects($this->never())->method('output');

        $generator = new PreviewGenerator();
        $generator->setGdAdapter($gd);

        ob_start();
        $generator->render('Un título');
        $output = ob_get_clean();

        $this->assertSame('fallback-png', $output);
    }

    /**
     * render() con GD disponible pero SIN soporte TTF → drawSimpleText().
     */
    public function testRenderOutputsPngWhenNoTtfSupport(): void
    {
        $gd = $this->createMock(GdAdapterInterface::class);
        $gd->method('hasGdSupport')->willReturn(true);
        $gd->method('hasTtfSupport')->willReturn(false);
        $gd->method('createImage')->willReturn('img');
        $gd->method('allocateColor')->willReturn(1);

        $gd->expects($this->once())->method('fillBackground');
        $gd->expects($this->once())->method('drawSimpleText');
        $gd->expects($this->never())->method('drawTtfText');
        $gd->expects($this->once())->method('output')->willReturnCallback(function () {
            echo "\x89PNGstub";
        });

        $generator = new PreviewGenerator();
        $generator->setGdAdapter($gd);

        ob_start();
        $generator->render('Hola');
        $output = ob_get_clean();

        $this->assertNotSame('', $output);
        $this->assertStringStartsWith("\x89PNG", $output);
    }

    /**
     * render() con GD Y soporte TTF disponibles → drawTtfText().
     */
    public function testRenderUsesTtfPathWhenAvailable(): void
    {
        $gd = $this->createMock(GdAdapterInterface::class);
        $gd->method('hasGdSupport')->willReturn(true);
        $gd->method('hasTtfSupport')->willReturn(true);
        $gd->method('createImage')->willReturn('img');
        $gd->method('allocateColor')->willReturn(1);
        $gd->method('getTextBoundingBox')->willReturn([0, 0, 100, 0, 100, -20, 0, -20]);

        $gd->expects($this->once())->method('fillBackground');
        $gd->expects($this->once())->method('drawTtfText');
        $gd->expects($this->never())->method('drawSimpleText');
        $gd->expects($this->once())->method('output')->willReturnCallback(function () {
            echo "\x89PNGstub";
        });

        $generator = new PreviewGenerator();
        $generator->setGdAdapter($gd);

        ob_start();
        $generator->render('Titulo');
        $output = ob_get_clean();

        $this->assertNotSame('', $output);
        $this->assertStringStartsWith("\x89PNG", $output);
    }

    // ─── métodos protegidos: verificados vía mocks ───────────────────────────────

    /**
     * serveStaticFallback() cuando el archivo NO existe → sin output (envía 404).
     */
    public function testServeStaticFallbackSends404WhenFileNotFound(): void
    {
        $generator = new class() extends PreviewGenerator {
            public function publicServeStaticFallback(): void
            {
                $this->serveStaticFallback();
            }
        };

        ob_start();
        $generator->publicServeStaticFallback();
        $output = ob_get_clean();

        $this->assertSame('', $output);
        $this->assertSame(404, http_response_code());
    }

    /**
     * serveStaticFallback() cuando el archivo SÍ existe → sirve el contenido.
     */
    public function testServeStaticFallbackServesFileWhenExists(): void
    {
        $imagePath = $this->tmpRoot . '/assets/images/og-image.png';
        file_put_contents($imagePath, 'real-png-bytes');

        $generator = new class() extends PreviewGenerator {
            public function publicServeStaticFallback(): void
            {
                $this->serveStaticFallback();
            }
        };

        ob_start();
        $generator->publicServeStaticFallback();
        $output = ob_get_clean();

        $this->assertSame('real-png-bytes', $output);
    }
}
