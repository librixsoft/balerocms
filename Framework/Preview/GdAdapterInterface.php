<?php

namespace Framework\Preview;

interface GdAdapterInterface
{
    public function hasGdSupport(): bool;

    public function hasTtfSupport(string $fontPath): bool;

    public function createImage(int $width, int $height);

    public function allocateColor($image, int $red, int $green, int $blue): int;

    public function fillBackground($image, int $width, int $height, int $color): void;

    public function drawSimpleText($image, int $width, int $height, string $title, int $color): void;

    public function getTextBoundingBox(int $fontSize, string $fontPath, string $title): array;

    public function drawTtfText($image, int $fontSize, int $x, int $y, int $color, string $fontPath, string $title): void;

    public function output($image): void;
}
