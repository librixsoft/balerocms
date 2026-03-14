<?php

namespace Framework\Preview;

class PreviewGenerator
{
    /**
     * Genera la URL dinámica para el meta tag og:image.
     */
    public function generatePreviewUrl(array $params): string
    {
        $cacheBuster = date('YmdHis');

        // Extraer base URL del config
        $rawUrl = $params['url'] ?? '';
        $parsed = parse_url($rawUrl);
        $baseUrl = ($parsed['scheme'] ?? 'http') . '://' . ($parsed['host'] ?? '');

        // 1. Si ya existe una imagen OG definida manualmente
        if (!empty($params['og_image'])) {
            $prefix = (strpos($params['og_image'], '?') === false) ? '?' : '&';
            if (strpos($params['og_image'], 'http') === 0) {
                return $params['og_image'] . $prefix . "v=" . $cacheBuster;
            }
            return $baseUrl . '/' . ltrim($params['og_image'], '/') . $prefix . "v=" . $cacheBuster;
        }

        // 2. Si hay una página con slug, generar URL hacia el controlador de imagen
        $page = $params['page'] ?? null;
        $slug = is_object($page) ? ($page->static_url ?? null) : ($page['static_url'] ?? null);

        if ($slug) {
            return "{$baseUrl}/page/og/" . urlencode($slug) . "?v={$cacheBuster}";
        }

        // 3. Fallback genérico usando el título
        $title = urlencode($params['title'] ?? 'Preview');
        return "{$baseUrl}/page/og/generic?title={$title}&v={$cacheBuster}";
    }

    /**
     * Procesa y sirve la imagen OG al navegador.
     */
    public function render(?string $title = null): void
    {
        if (empty($title)) {
            $this->serveStaticFallback();
            return;
        }

        if (!$this->hasGdSupport()) {
            $this->serveStaticFallback();
            return;
        }

        $width = 1200;
        $height = 627;
        $image = $this->createImage($width, $height);

        $bgColor = $this->allocateColor($image, 26, 26, 29);
        $textColor = $this->allocateColor($image, 255, 255, 255);
        $this->fillBackground($image, $width, $height, $bgColor);

        $fontPath = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/assets/fonts/Roboto-Bold.ttf';

        if ($this->hasTtfSupport($fontPath)) {
            $fontSize = 60;
            $bbox = $this->getTextBoundingBox($fontSize, $fontPath, $title);
            $textWidth = abs($bbox[4] - $bbox[0]);
            $textHeight = abs($bbox[5] - $bbox[1]);
            $x = (int) (($width - $textWidth) / 2);
            $y = (int) (($height + $textHeight) / 2);
            $this->drawTtfText($image, $fontSize, $x, $y, $textColor, $fontPath, $title);
        } else {
            // Fallback simple si no hay fuentes TTF
            $this->drawSimpleText($image, $width, $height, $title, $textColor);
        }

        $this->output($image);
    }

    protected function hasGdSupport(): bool
    {
        return function_exists('imagecreatetruecolor')
            && function_exists('imagecolorallocate')
            && function_exists('imagefilledrectangle')
            && function_exists('imagepng')
            && function_exists('imagedestroy');
    }

    protected function createImage(int $width, int $height)
    {
        return \imagecreatetruecolor($width, $height);
    }

    protected function allocateColor($image, int $red, int $green, int $blue): int
    {
        return \imagecolorallocate($image, $red, $green, $blue);
    }

    protected function fillBackground($image, int $width, int $height, int $color): void
    {
        \imagefilledrectangle($image, 0, 0, $width, $height, $color);
    }

    protected function drawSimpleText($image, int $width, int $height, string $title, int $color): void
    {
        \imagestring($image, 5, ($width / 2) - (strlen($title) * 4), $height / 2, $title, $color);
    }

    protected function hasTtfSupport(string $fontPath): bool
    {
        return file_exists($fontPath)
            && function_exists('imagettftext')
            && function_exists('imagettfbbox');
    }

    protected function getTextBoundingBox(int $fontSize, string $fontPath, string $title): array
    {
        return \imagettfbbox($fontSize, 0, $fontPath, $title);
    }

    protected function drawTtfText($image, int $fontSize, int $x, int $y, int $color, string $fontPath, string $title): void
    {
        \imagettftext($image, $fontSize, 0, $x, $y, $color, $fontPath, $title);
    }

    protected function serveStaticFallback(): void
    {
        $path = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/assets/images/og-image.png';
        if (!file_exists($path)) {
            http_response_code(404);
            return;
        }
        header('Content-Type: image/png');
        readfile($path);
    }

    protected function output($image): void
    {
        header('Content-Type: image/png');
        header('Cache-Control: public, max-age=86400');
        \imagepng($image);
        \imagedestroy($image);
    }
}