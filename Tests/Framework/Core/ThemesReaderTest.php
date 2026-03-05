<?php

declare(strict_types=1);

namespace Tests\Framework\Core;

use Framework\Core\ThemesReader;
use PHPUnit\Framework\TestCase;

final class ThemesReaderTest extends TestCase
{
    public function testGetThemesReturnsDirectoriesOnly(): void
    {
        $base = sys_get_temp_dir() . '/themes-' . uniqid();
        @mkdir($base . '/a', 0777, true);
        @mkdir($base . '/b', 0777, true);
        file_put_contents($base . '/readme.txt', 'x');

        $reader = new ThemesReader();
        $r = new \ReflectionClass($reader);
        $p = $r->getProperty('themesPath');
        $p->setAccessible(true);
        $p->setValue($reader, $base);

        $themes = $reader->getThemes();
        sort($themes);

        $this->assertSame(['a', 'b'], $themes);

        @unlink($base . '/readme.txt');
        @rmdir($base . '/a');
        @rmdir($base . '/b');
        @rmdir($base);
    }
}
