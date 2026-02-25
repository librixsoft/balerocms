<?php

namespace App\Services;

class PreviewService
{
    /**
     * Generates and outputs an Open Graph image with the given title.
     *
     * @param string $title The text to display on the image.
     */
    public function generateOpenGraphImage(string $title): void
    {
        // 1200x627 is standard Open Graph image size
        $width = 1200;
        $height = 627;

        $image = imagecreatetruecolor($width, $height);

        // Background color #1a1a1d
        $bgColor = imagecolorallocate($image, 26, 26, 29);
        imagefilledrectangle($image, 0, 0, $width, $height, $bgColor);

        // Text color #ffffff
        $textColor = imagecolorallocate($image, 255, 255, 255);

        // Path to font
        $fontPath = rtrim(BASE_PATH, '/') . '/public/assets/fonts/Roboto-Bold.ttf';
        
        $fontSize = 60;

        if (file_exists($fontPath) && function_exists('imagettftext')) {
            // Get bounding box of text for centering
            $bbox = imagettfbbox($fontSize, 0, $fontPath, $title);
            $textWidth = abs($bbox[4] - $bbox[0]);
            $textHeight = abs($bbox[5] - $bbox[1]);

            $x = (int) (($width - $textWidth) / 2);
            $y = (int) (($height + $textHeight) / 2); // Y is baseline for imagettftext

            imagettftext($image, $fontSize, 0, $x, $y, $textColor, $fontPath, $title);
        } else {
            // Fallback if font or FreeType not available
            $font = 5;
            $tw = imagefontwidth($font) * strlen($title);
            $th = imagefontheight($font);

            $scale = 4;
            $scaledW = $tw * $scale;
            $scaledH = $th * $scale;

            $small = imagecreatetruecolor($tw, $th);
            $smallBg = imagecolorallocate($small, 26, 26, 29);
            $smallTxt = imagecolorallocate($small, 255, 255, 255);

            imagefilledrectangle($small, 0, 0, $tw, $th, $smallBg);
            imagestring($small, $font, 0, 0, $title, $smallTxt);

            $x = ($width - $scaledW) / 2;
            $y = ($height - $scaledH) / 2;

            imagecopyresampled($image, $small, $x, $y, 0, 0, $scaledW, $scaledH, $tw, $th);
            imagedestroy($small);
        }

        header('Content-Type: image/png');
        header('Cache-Control: public, max-age=86400'); // Cache for 1 day
        imagepng($image);
        imagedestroy($image);
        exit;
    }
}
