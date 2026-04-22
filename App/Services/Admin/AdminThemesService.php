<?php

namespace App\Services\Admin;

use App\Models\Admin\AdminPagesModel;
use App\Models\Admin\AdminBlocksModel;
use App\Models\Admin\AdminMediaModel;
use App\Views\AdminViewModel;
use Framework\Attributes\Inject;
use Framework\Attributes\Service;
use Framework\Core\ConfigSettings;
use App\Exceptions\Admin\ThemeException;

#[Service]
class AdminThemesService
{
    private const THEMES_PATH = '/resources/views/themes/';

    #[Inject]
    private AdminPagesModel $pagesModel;

    #[Inject]
    private AdminBlocksModel $blocksModel;

    #[Inject]
    private AdminMediaModel $mediaModel;

    #[Inject]
    private AdminViewModel $viewModel;

    #[Inject]
    private ConfigSettings $configSettings;

    public function getThemesViewParams(): array
    {
        $params = [
            'pages_count'  => $this->pagesModel->getPagesCount(),
            'blocks_count' => $this->blocksModel->getBlocksCount(),
            'media_count'  => count($this->mediaModel->getAllMedia()),
        ];

        return $this->viewModel->getThemesParams($params);
    }

    public function uploadThemeZip(array $file): void
    {
        $zip = $this->openAndValidateZip($file['tmp_name']);
        $themeName = $this->extractThemeName($file['name'], $zip);

        [$publicThemesDir, $resourcesThemesDir] = $this->initThemeDirectories($themeName);
        $rootDir = $this->findThemeRoot($zip);

        $this->processZipEntries($zip, $rootDir, $publicThemesDir, $resourcesThemesDir);
        $zip->close();
    }

    public function activateTheme(string $themeName): void
    {
        $resourcesThemesDir = rtrim(BASE_PATH, '/') . self::THEMES_PATH . $themeName;
        if (!is_dir($resourcesThemesDir)) {
            throw new ThemeException("Theme does not exist.");
        }

        $this->configSettings->theme = $themeName;
    }

    public function deleteTheme(string $themeName): void
    {
        $themeName = preg_replace('/[^a-zA-Z0-9_\-]/', '', $themeName);
        if (empty($themeName)) {
            throw new ThemeException("Invalid theme name.");
        }

        if ($this->configSettings->theme === $themeName) {
            throw new ThemeException("Cannot delete the active theme.");
        }

        $publicPath = !empty($_SERVER['DOCUMENT_ROOT'])
            ? rtrim($_SERVER['DOCUMENT_ROOT'], '/')
            : rtrim(BASE_PATH, '/') . '/public';

        $resourcesThemesDir = rtrim(BASE_PATH, '/') . self::THEMES_PATH . $themeName;
        $publicThemesDir = $publicPath . '/assets/themes/' . $themeName;

        $this->removeDirectory($resourcesThemesDir);
        $this->removeDirectory($publicThemesDir);
    }

    private function openAndValidateZip(string $zipPath): \ZipArchive
    {
        $zip = new \ZipArchive();
        if ($zip->open($zipPath) !== true) {
            throw new ThemeException("Invalid ZIP file.");
        }
        return $zip;
    }

    private function extractThemeName(string $filename, \ZipArchive $zip): string
    {
        $themeName = pathinfo($filename, PATHINFO_FILENAME);
        $themeName = preg_replace('/[^a-zA-Z0-9_\-]/', '', $themeName);
        if (empty($themeName)) {
            $zip->close();
            throw new ThemeException("Invalid theme name.");
        }
        return $themeName;
    }


    private function initThemeDirectories(string $themeName): array
    {
        $publicPath = !empty($_SERVER['DOCUMENT_ROOT'])
            ? rtrim($_SERVER['DOCUMENT_ROOT'], '/')
            : rtrim(BASE_PATH, '/') . '/public';

        $publicThemesDir = $publicPath . '/assets/themes/' . $themeName;
        $resourcesThemesDir = rtrim(BASE_PATH, '/') . self::THEMES_PATH . $themeName;

        if (is_dir($publicThemesDir)) {
            $this->removeDirectory($publicThemesDir);
        }
        if (is_dir($resourcesThemesDir)) {
            $this->removeDirectory($resourcesThemesDir);
        }

        mkdir($publicThemesDir, 0755, true);
        mkdir($resourcesThemesDir, 0755, true);

        return [$publicThemesDir, $resourcesThemesDir];
    }

    private function findThemeRoot(\ZipArchive $zip): string
    {
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = str_replace('\\', '/', $zip->getNameIndex($i));
            if (basename($name) === 'main.html') {
                $rootDir = dirname($name);
                return ($rootDir === '.' || $rootDir === '') ? '' : $rootDir . '/';
            }
        }
        return '';
    }

    private function processZipEntries(\ZipArchive $zip, string $rootDir, string $publicThemesDir, string $resourcesThemesDir): void
    {
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $filename = str_replace('\\', '/', $zip->getNameIndex($i));
            if (substr($filename, -1) == '/') {
                continue;
            }

            $relativePath = $this->getRelativeZipPath($filename, $rootDir);
            if ($relativePath === null) {
                continue;
            }

            $content = $zip->getFromIndex($i);
            if ($relativePath === 'main.html') {
                file_put_contents($resourcesThemesDir . '/main.html', $content);
            } else {
                $this->saveThemeAsset($publicThemesDir, $relativePath, $content);
            }
        }
    }

    private function getRelativeZipPath(string $filename, string $rootDir): ?string
    {
        if ($rootDir !== '' && strpos($filename, $rootDir) === 0) {
            return substr($filename, strlen($rootDir));
        }

        if ($rootDir === '') {
            return $filename;
        }

        return null;
    }

    private function saveThemeAsset(string $publicThemesDir, string $relativePath, string $content): void
    {
        $destPath = $publicThemesDir . '/' . $relativePath;
        $destDir = dirname($destPath);
        if (!is_dir($destDir)) {
            mkdir($destDir, 0755, true);
        }
        file_put_contents($destPath, $content);
    }

    private function removeDirectory(string $dir): void
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
                @rmdir($file->getPathname());
            } else {
                @unlink($file->getPathname());
            }
        }
        @rmdir($dir);
    }
}
