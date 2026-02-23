<?php

namespace App\Services;

use Framework\Attributes\Service;

#[Service]
class UpdateService
{
    private const REPO_URL = 'https://raw.githubusercontent.com/librixsoft/balerocms/development/public/version.php';
    private const GITHUB_REPO = 'https://github.com/librixsoft/balerocms/tree/development';
    private const SERVICE_URL = 'https://raw.githubusercontent.com/librixsoft/balerocms/development/App/Services/UpdateService.php';

    public function getCurrentVersion(): string
    {
        if (!defined('_CORE_VERSION')) {
            $path = $_SERVER['DOCUMENT_ROOT'] . '/version.php';
            if (file_exists($path)) {
                include_once $path;
            }
            if (!defined('_CORE_VERSION')) {
                return 'Unknown';
            }
        }
        return _CORE_VERSION;
    }

    public function getRemoteVersion(): ?string
    {
        if (!function_exists('curl_init')) {
            return null;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, self::REPO_URL);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'BaleroCMS-Updater');
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        $content = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($content === false || $httpCode !== 200) {
            return null;
        }

        if (preg_match('/const _CORE_VERSION = "(.*?)";/', $content, $matches)) {
            return $matches[1];
        }

        return null;
    }

    public function isUpdateAvailable(): array
    {
        $current = $this->getCurrentVersion();
        $remote = $this->getRemoteVersion();
        $updateAvailable = false;

        if ($remote && version_compare($remote, $current, '>')) {
            $updateAvailable = true;
        }

        return [
            'current_version' => $current,
            'remote_version' => $remote ?? 'Unknown',
            'update_available' => $updateAvailable,
            'repo_url' => self::GITHUB_REPO
        ];
    }

    /**
     * Self-update UpdateService.php from repo
     */
    public function selfUpdate(): array
    {
        if (!function_exists('curl_init')) {
            return ['success' => false, 'message' => 'cURL is not available'];
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, self::SERVICE_URL);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'BaleroCMS-Updater');
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        $content = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($content === false || $httpCode !== 200) {
            return ['success' => false, 'message' => 'Failed to download UpdateService.php from repo'];
        }

        if (file_put_contents(__FILE__, $content) === false) {
            return ['success' => false, 'message' => 'Failed to write UpdateService.php'];
        }

        return ['success' => true, 'message' => 'UpdateService.php self-updated successfully'];
    }

    /**
     * Download the update ZIP from GitHub
     */
    public function downloadUpdate(): array
    {
        $zipUrl = 'https://github.com/librixsoft/balerocms/archive/refs/heads/development.zip';
        $tempDir = sys_get_temp_dir();
        $zipFile = $tempDir . '/balerocms-update.zip';

        if (!function_exists('curl_init')) {
            return ['success' => false, 'message' => 'cURL is not available'];
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $zipUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'BaleroCMS-Updater');
        curl_setopt($ch, CURLOPT_TIMEOUT, 300);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        $content = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($content === false || $httpCode !== 200) {
            return ['success' => false, 'message' => 'Failed to download update'];
        }

        if (file_put_contents($zipFile, $content) === false) {
            return ['success' => false, 'message' => 'Failed to save update file'];
        }

        return ['success' => true, 'zip_file' => $zipFile];
    }

    /**
     * Extract the downloaded ZIP
     */
    public function extractUpdate(string $zipFile): array
    {
        if (!class_exists('ZipArchive')) {
            return ['success' => false, 'message' => 'ZipArchive extension is not available'];
        }

        $tempDir = sys_get_temp_dir() . '/balerocms-update-' . time();

        $zip = new \ZipArchive();
        if ($zip->open($zipFile) !== true) {
            return ['success' => false, 'message' => 'Failed to open ZIP file'];
        }

        if (!$zip->extractTo($tempDir)) {
            $zip->close();
            return ['success' => false, 'message' => 'Failed to extract ZIP file'];
        }

        $zip->close();

        $extractedFolder = $tempDir . '/balerocms-development';

        if (!is_dir($extractedFolder)) {
            return ['success' => false, 'message' => 'Extracted folder not found'];
        }

        return ['success' => true, 'extracted_folder' => $extractedFolder];
    }

    /**
     * Install the update
     */
    public function installUpdate(string $extractedFolder): array
    {
        $rootPath = defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 2);

        $dirsToUpdate = ['App', 'Framework', 'public', 'resources'];

        foreach ($dirsToUpdate as $dir) {
            $source = $extractedFolder . '/' . $dir;

            if ($dir === 'public') {
                $destination = $_SERVER['DOCUMENT_ROOT'];
            } else {
                $destination = $rootPath . '/' . $dir;
            }

            if (!is_dir($source)) {
                continue;
            }

            if (!$this->copyDirectory($source, $destination)) {
                return ['success' => false, 'message' => "Failed to copy $dir"];
            }
        }

        $versionSource = $extractedFolder . '/public/version.php';
        $versionDest = $_SERVER['DOCUMENT_ROOT'] . '/version.php';
        if (file_exists($versionSource)) {
            copy($versionSource, $versionDest);
        }

        return ['success' => true, 'message' => 'Update installed successfully'];
    }

    /**
     * Recursively copy directory (helper method)
     */
    private function copyDirectory(string $source, string $destination): bool
    {
        if (!is_dir($destination)) {
            if (!mkdir($destination, 0755, true)) {
                return false;
            }
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($files as $file) {
            $targetPath = $destination . '/' . substr($file->getPathname(), strlen($source) + 1);

            if ($file->isDir()) {
                if (!is_dir($targetPath)) {
                    mkdir($targetPath, 0755, true);
                }
            } else {
                if (strpos($targetPath, '/resources/config/balero.config.json') !== false ||
                    strpos($targetPath, '/assets/images/uploads/') !== false ||
                    strpos($targetPath, '/resources/views/themes/') !== false ||
                    strpos($targetPath, '/resources/config/') !== false ||
                    strpos($targetPath, '/favicon.ico') !== false) {
                    continue;
                }

                copy($file->getPathname(), $targetPath);
            }
        }

        return true;
    }

    /**
     * Perform complete update process
     */
    public function performUpdate(): array
    {
        // Step 1: Download
        $downloadResult = $this->downloadUpdate();
        if (!$downloadResult['success']) {
            return $downloadResult;
        }

        // Step 2: Extract
        $extractResult = $this->extractUpdate($downloadResult['zip_file']);
        if (!$extractResult['success']) {
            @unlink($downloadResult['zip_file']);
            return $extractResult;
        }

        // Step 3: Install
        $installResult = $this->installUpdate($extractResult['extracted_folder']);

        // Cleanup
        @unlink($downloadResult['zip_file']);
        $this->removeDirectory($extractResult['extracted_folder']);

        return $installResult;
    }

    /**
     * Remove directory recursively (helper method)
     */
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
                rmdir($file->getPathname());
            } else {
                unlink($file->getPathname());
            }
        }

        rmdir($dir);
    }
}