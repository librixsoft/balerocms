<?php

namespace App\Services;

class PreviewService
{
    /**
     * Serves the static fallback OG image.
     */
    public function serveStaticOgImage(): void
    {
        $imagePath = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/assets/images/og-image.png';

        if (!file_exists($imagePath)) {
            http_response_code(404);
            exit;
        }

        header('Content-Type: image/png');
        header('Cache-Control: public, max-age=86400');
        readfile($imagePath);
        exit;
    }

    /**
     * Resolves and serves the correct OG image for a given page.
     * If the page is null/empty, serves the static fallback.
     *
     * @param object|array|null $page
     */
    public function serveOgImage(mixed $page): void
    {
        if (empty($page)) {
            $this->serveStaticOgImage();
            return;
        }

        $title = is_object($page) ? $page->virtual_title : $page['virtual_title'];
        $this->generateOpenGraphImage($title);
    }

    /**
     * Generates and outputs an Open Graph image with the given title.
     *
     * @param string $title The text to display on the image.
     */
    public function generateOpenGraphImage(string $title): void
    {
        $width  = 1200;
        $height = 627;

        $image = imagecreatetruecolor($width, $height);

        $bgColor   = imagecolorallocate($image, 26, 26, 29);
        $textColor = imagecolorallocate($image, 255, 255, 255);

        imagefilledrectangle($image, 0, 0, $width, $height, $bgColor);

        $fontPath = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/assets/fonts/Roboto-Bold.ttf';
        $fontSize = 60;

        if (file_exists($fontPath) && function_exists('imagettftext')) {
            $bbox       = imagettfbbox($fontSize, 0, $fontPath, $title);
            $textWidth  = abs($bbox[4] - $bbox[0]);
            $textHeight = abs($bbox[5] - $bbox[1]);

            $x = (int) (($width - $textWidth) / 2);
            $y = (int) (($height + $textHeight) / 2);

            imagettftext($image, $fontSize, 0, $x, $y, $textColor, $fontPath, $title);
        } else {
            $font    = 5;
            $tw      = imagefontwidth($font) * strlen($title);
            $th      = imagefontheight($font);
            $scale   = 4;
            $scaledW = $tw * $scale;
            $scaledH = $th * $scale;

            $small   = imagecreatetruecolor($tw, $th);
            $smallBg = imagecolorallocate($small, 26, 26, 29);
            $smallTx = imagecolorallocate($small, 255, 255, 255);

            imagefilledrectangle($small, 0, 0, $tw, $th, $smallBg);
            imagestring($small, $font, 0, 0, $title, $smallTx);

            $x = ($width - $scaledW) / 2;
            $y = ($height - $scaledH) / 2;

            imagecopyresampled($image, $small, $x, $y, 0, 0, $scaledW, $scaledH, $tw, $th);
            imagedestroy($small);
        }

        header('Content-Type: image/png');
        header('Cache-Control: public, max-age=86400');
        imagepng($image);
        imagedestroy($image);
        exit;
    }
}