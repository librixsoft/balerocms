<?php

namespace Framework\Preview;

/**
 * @codeCoverageIgnore
 */
class NativeGdAdapter implements GdAdapterInterface
{
    public function hasGdSupport(): bool
    {
        return function_exists('imagecreatetruecolor')
            && function_exists('imagecolorallocate')
            && function_exists('imagefilledrectangle')
            && function_exists('imagepng')
            && function_exists('imagedestroy');
    }

    public function hasTtfSupport(string $fontPath): bool
    {
        return file_exists($fontPath)
            && function_exists('imagettftext')
            && function_exists('imagettfbbox');
    }

    public function createImage(int $width, int $height)
    {
        return \imagecreatetruecolor($width, $height);
    }

    public function allocateColor($image, int $red, int $green, int $blue): int
    {
        return \imagecolorallocate($image, $red, $green, $blue);
    }

    public function fillBackground($image, int $width, int $height, int $color): void
    {
        \imagefilledrectangle($image, 0, 0, $width, $height, $color);
    }

    public function drawSimpleText($image, int $width, int $height, string $title, int $color): void
    {
        \imagestring($image, 5, ($width / 2) - (strlen($title) * 4), $height / 2, $title, $color);
    }

    public function getTextBoundingBox(int $fontSize, string $fontPath, string $title): array
    {
        return \imagettfbbox($fontSize, 0, $fontPath, $title);
    }

    public function drawTtfText($image, int $fontSize, int $x, int $y, int $color, string $fontPath, string $title): void
    {
        \imagettftext($image, $fontSize, 0, $x, $y, $color, $fontPath, $title);
    }

    public function output($image): void
    {
        header('Content-Type: image/png');
        header('Cache-Control: public, max-age=86400');
        \imagepng($image);
        \imagedestroy($image);
    }
}
