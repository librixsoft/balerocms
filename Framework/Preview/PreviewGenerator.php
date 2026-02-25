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
    public function render(string $title = null): void
    {
        if (empty($title)) {
            $this->serveStaticFallback();
            return;
        }

        $width = 1200;
        $height = 627;
        $image = imagecreatetruecolor($width, $height);

        $bgColor = imagecolorallocate($image, 26, 26, 29);
        $textColor = imagecolorallocate($image, 255, 255, 255);
        imagefilledrectangle($image, 0, 0, $width, $height, $bgColor);

        $fontPath = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/assets/fonts/Roboto-Bold.ttf';

        if (file_exists($fontPath) && function_exists('imagettftext')) {
            $fontSize = 60;
            $bbox = imagettfbbox($fontSize, 0, $fontPath, $title);
            $textWidth = abs($bbox[4] - $bbox[0]);
            $textHeight = abs($bbox[5] - $bbox[1]);
            $x = (int) (($width - $textWidth) / 2);
            $y = (int) (($height + $textHeight) / 2);
            imagettftext($image, $fontSize, 0, $x, $y, $textColor, $fontPath, $title);
        } else {
            // Fallback simple si no hay fuentes TTF
            imagestring($image, 5, ($width / 2) - (strlen($title) * 4), $height / 2, $title, $textColor);
        }

        $this->output($image);
    }

    private function serveStaticFallback(): void
    {
        $path = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/assets/images/og-image.png';
        if (!file_exists($path)) {
            http_response_code(404);
            exit;
        }
        header('Content-Type: image/png');
        readfile($path);
        exit;
    }

    private function output($image): void
    {
        header('Content-Type: image/png');
        header('Cache-Control: public, max-age=86400');
        imagepng($image);
        imagedestroy($image);
        exit;
    }
}