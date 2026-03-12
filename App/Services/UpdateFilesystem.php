<?php

namespace App\Services;

class UpdateFilesystem
{
    /** @var string[] Relative paths that must never be overwritten */
    private array $protectedPaths;

    /**
     * @param string[] $protectedPaths
     */
    public function __construct(array $protectedPaths)
    {
        $this->protectedPaths = $protectedPaths;
    }

    public function copyDirectory(string $source, string $destination): bool
    {
        $success = true;

        if ($this->pathExistsAsFile($destination)) {
            $success = false;
        }

        if ($success && !$this->ensureDirectory($destination)) {
            $success = false;
        }

        if ($success) {
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($files as $file) {
                if (!$this->copyDirectoryEntry($file, $source, $destination)) {
                    $success = false;
                    break;
                }
            }
        }

        return $success;
    }

    /** Returns true when $path matches any protected-path rule. */
    public function isProtectedPath(string $path): bool
    {
        foreach ($this->protectedPaths as $protected) {
            if (str_contains($path, $protected)) {
                return true;
            }
        }
        return false;
    }

    public function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $file) {
            if ($file->isDir()) {
                rmdir($file->getPathname());
            } else {
                unlink($file->getPathname());
            }
        }

        rmdir($dir);
    }

    private function pathExistsAsFile(string $path): bool
    {
        return file_exists($path) && !is_dir($path);
    }

    private function ensureDirectory(string $path): bool
    {
        return is_dir($path) || (mkdir($path, 0755, true) && is_dir($path));
    }

    private function copyDirectoryEntry(\SplFileInfo $file, string $source, string $destination): bool
    {
        $targetPath = $destination . '/' . substr($file->getPathname(), strlen($source) + 1);
        $result = true;

        if ($file->isDir()) {
            $result = !$this->pathExistsAsFile($targetPath)
                && $this->ensureDirectory($targetPath);
        } elseif ($this->isProtectedPath($targetPath)) {
            $result = true;
        } elseif (is_dir($targetPath)) {
            $result = false;
        } else {
            $result = copy($file->getPathname(), $targetPath);
        }

        return $result;
    }
}
