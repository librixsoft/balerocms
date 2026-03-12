<?php

declare(strict_types=1);

namespace Tests\App\Services;

use App\Services\UpdateFilesystem;
use PHPUnit\Framework\TestCase;

final class UpdateFilesystemTest extends TestCase
{
    private function makeTempRoot(string $prefix = 'ufs'): string
    {
        $dir = sys_get_temp_dir() . '/' . $prefix . '-' . uniqid();
        mkdir($dir, 0777, true);
        return $dir;
    }

    private function makeFilesystem(): UpdateFilesystem
    {
        return new UpdateFilesystem([
            '/resources/config/balero.config.json',
            '/assets/images/uploads/',
            '/resources/views/themes/',
            '/resources/config/',
            '/favicon.ico',
        ]);
    }

    public function testCopyDirectoryRecursivelyCopiesFiles(): void
    {
        $src  = $this->makeTempRoot('src');
        $dst  = sys_get_temp_dir() . '/dst-' . uniqid();
        mkdir($src . '/sub', 0777, true);
        file_put_contents($src . '/a.txt', 'A');
        file_put_contents($src . '/sub/b.txt', 'B');

        $fs = $this->makeFilesystem();
        $result = $fs->copyDirectory($src, $dst);

        $this->assertTrue($result);
        $this->assertFileExists($dst . '/a.txt');
        $this->assertFileExists($dst . '/sub/b.txt');
        $this->assertSame('A', file_get_contents($dst . '/a.txt'));

        $fs->removeDirectory($dst);
    }

    public function testCopyDirectorySkipsProtectedPaths(): void
    {
        $src = $this->makeTempRoot('src-prot');
        $dst = sys_get_temp_dir() . '/dst-prot-' . uniqid();
        mkdir($src . '/resources/config', 0777, true);
        file_put_contents($src . '/resources/config/balero.config.json', '{"secret":1}');
        file_put_contents($src . '/normal.txt', 'ok');

        $fs = $this->makeFilesystem();
        $fs->copyDirectory($src, $dst);

        $this->assertFileDoesNotExist($dst . '/resources/config/balero.config.json');
        $this->assertFileExists($dst . '/normal.txt');

        $fs->removeDirectory($dst);
    }

    public function testCopyDirectoryCreatesDestinationIfMissing(): void
    {
        $src = $this->makeTempRoot('src-new');
        file_put_contents($src . '/file.txt', 'hello');
        $dst = sys_get_temp_dir() . '/dst-new-' . uniqid();

        $fs = $this->makeFilesystem();
        $result = $fs->copyDirectory($src, $dst);

        $this->assertTrue($result);
        $this->assertDirectoryExists($dst);

        $fs->removeDirectory($dst);
    }

    public function testCopyDirectoryReturnsFalseWhenDestinationMkdirFails(): void
    {
        $src = $this->makeTempRoot('src-mkdir-fail');
        file_put_contents($src . '/file.txt', 'x');

        $dst = tempnam(sys_get_temp_dir(), 'dst-file-');

        $fs = $this->makeFilesystem();
        $result = $fs->copyDirectory($src, $dst);

        $this->assertFalse($result);

        @unlink($dst);
    }

    public function testCopyDirectoryReturnsFalseWhenCopyFails(): void
    {
        $src = $this->makeTempRoot('src-copy-fail');
        file_put_contents($src . '/file.txt', 'x');

        $dst = $this->makeTempRoot('dst-copy-fail');
        mkdir($dst . '/file.txt', 0777, true); // make target path a directory to force copy failure

        $fs = $this->makeFilesystem();
        $result = $fs->copyDirectory($src, $dst);

        $this->assertFalse($result);

        $fs->removeDirectory($dst);
    }

    public function testCopyDirectoryReturnsFalseWhenDestinationIsFile(): void
    {
        $src = $this->makeTempRoot('src-dest-file');
        file_put_contents($src . '/file.txt', 'x');

        $dst = tempnam(sys_get_temp_dir(), 'dst-file-exists-');

        $fs = $this->makeFilesystem();
        $result = $fs->copyDirectory($src, $dst);

        $this->assertFalse($result);

        @unlink($dst);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('protectedPathProvider')]
    public function testIsProtectedPathReturnsTrueForKnownProtectedPaths(string $path): void
    {
        $fs = $this->makeFilesystem();
        $this->assertTrue($fs->isProtectedPath($path));
    }

    public static function protectedPathProvider(): array
    {
        return [
            ['/var/www/resources/config/balero.config.json'],
            ['/var/www/assets/images/uploads/photo.jpg'],
            ['/var/www/resources/views/themes/default/index.html'],
            ['/var/www/resources/config/app.php'],
            ['/var/www/public/favicon.ico'],
        ];
    }

    public function testIsProtectedPathReturnsFalseForNormalPaths(): void
    {
        $fs = $this->makeFilesystem();
        $this->assertFalse($fs->isProtectedPath('/var/www/App/Controllers/HomeController.php'));
        $this->assertFalse($fs->isProtectedPath('/var/www/public/index.php'));
    }

    public function testRemoveDirectoryDeletesNestedContent(): void
    {
        $dir = $this->makeTempRoot('rm');
        mkdir($dir . '/sub/deep', 0777, true);
        file_put_contents($dir . '/sub/deep/file.txt', 'x');

        $fs = $this->makeFilesystem();
        $fs->removeDirectory($dir);

        $this->assertDirectoryDoesNotExist($dir);
    }

    public function testRemoveDirectoryDoesNothingForNonExistentPath(): void
    {
        $fs = $this->makeFilesystem();
        $fs->removeDirectory('/this/does/not/exist-' . uniqid());
        $this->assertTrue(true);
    }

    public function testRemoveDirectoryHandlesEmptyDirectory(): void
    {
        $dir = $this->makeTempRoot('rm-empty');
        $fs = $this->makeFilesystem();
        $fs->removeDirectory($dir);
        $this->assertDirectoryDoesNotExist($dir);
    }
}
