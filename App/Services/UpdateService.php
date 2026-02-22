<?php

namespace App\Services;

use Framework\Attributes\Service;

#[Service]
class UpdateService
{
    private const REPO_URL = 'https://raw.githubusercontent.com/librixsoft/balerocms/development/public/version.php';
    private const GITHUB_REPO = 'https://github.com/librixsoft/balerocms/tree/development';

    public function getCurrentVersion(): string
    {
        if (!defined('_CORE_VERSION')) {
             $projectRoot = defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 2);
             
             // Prevent recursive nesting block
             if (basename($projectRoot) === 'public_html' || basename($projectRoot) === 'public') {
                 if (is_dir(dirname($projectRoot) . '/App')) {
                     $projectRoot = dirname($projectRoot);
                 }
             }
             
             $publicRoot = $projectRoot . '/public';
             if (is_dir($projectRoot . '/public_html')) {
                 $publicRoot = $projectRoot . '/public_html';
             }

             $path = $publicRoot . '/version.php';
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
        // Use cURL instead of file_get_contents to avoid allow_url_fopen restrictions
        if (!function_exists('curl_init')) {
            return null; // cURL not available
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
     * Download the update ZIP from GitHub
     */
    public function downloadUpdate(): array
    {
        $zipUrl = 'https://github.com/librixsoft/balerocms/archive/refs/heads/development.zip?v=' . time();
        $tempDir = sys_get_temp_dir();
        $zipFile = $tempDir . '/balerocms-update.zip';

        // Download using cURL
        if (!function_exists('curl_init')) {
            return ['success' => false, 'message' => 'cURL is not available'];
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $zipUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'BaleroCMS-Updater');
        curl_setopt($ch, CURLOPT_TIMEOUT, 300); // 5 minutes for large files
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        $content = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($content === false || $httpCode !== 200) {
            return ['success' => false, 'message' => 'Failed to download update'];
        }

        // Save ZIP file
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

        // GitHub creates a folder like "balerocms-development" inside the ZIP
        $extractedFolder = $tempDir . '/balerocms-development';
        
        if (!is_dir($extractedFolder)) {
            return ['success' => false, 'message' => 'Extracted folder not found'];
        }

        return ['success' => true, 'extracted_folder' => $extractedFolder];
    }

    /**
     * Create backup of current installation
     */
    public function createBackup(): array
    {
        $projectRoot = dirname(__DIR__, 2);
        $backupDir = $projectRoot . '/backups';
        if (!is_dir($backupDir)) {
            if (!mkdir($backupDir, 0755, true)) {
                return ['success' => false, 'message' => 'Failed to create backup directory'];
            }
        }

        $backupFile = $backupDir . '/backup-' . date('Y-m-d-His') . '.zip';
        
        if (!class_exists('ZipArchive')) {
            return ['success' => false, 'message' => 'ZipArchive extension is not available'];
        }

        $zip = new \ZipArchive();
        if ($zip->open($backupFile, \ZipArchive::CREATE) !== true) {
            return ['success' => false, 'message' => 'Failed to create backup ZIP'];
        }

        $this->addFilesToZip($zip, $projectRoot, $projectRoot);
        
        $zip->close();

        return ['success' => true, 'backup_file' => $backupFile];
    }

    /**
     * Recursively add files to ZIP (helper method)
     */
    private function addFilesToZip(\ZipArchive $zip, string $path, string $rootPath): void
    {
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $file) {
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen($rootPath) + 1);
                
                // Skip certain directories
                if (strpos($relativePath, 'backups/') === 0 ||
                    strpos($relativePath, 'public/assets/images/uploads/') === 0) {
                    continue;
                }
                
                $zip->addFile($filePath, $relativePath);
            }
        }
    }

    /**
     * Install the update
     */
    public function installUpdate(string $extractedFolder): array
    {
        $projectRoot = defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 2);
        
        // Prevent recursive nesting if UpdateService.php was accidentally copied inside public_html in a prior bug
        if (basename($projectRoot) === 'public_html' || basename($projectRoot) === 'public') {
            if (is_dir(dirname($projectRoot) . '/App')) {
                $projectRoot = dirname($projectRoot);
            }
        }
        
        // Dynamically detect the correct public folder based strictly on project directories
        $publicRoot = $projectRoot . '/public';
        if (is_dir($projectRoot . '/public_html')) {
            $publicRoot = $projectRoot . '/public_html';
        } elseif (is_dir($projectRoot . '/www')) {
            $publicRoot = $projectRoot . '/www';
        }
        
        // Directories to update mapping (source => destination)
        $dirsMap = [
            'App' => $projectRoot . '/App',
            'Framework' => $projectRoot . '/Framework',
            'resources' => $projectRoot . '/resources',
            'public' => $publicRoot
        ];
        
        foreach ($dirsMap as $srcDir => $destDir) {
            $source = $extractedFolder . '/' . $srcDir;
            
            if (!is_dir($source)) {
                continue;
            }
            
            // Copy files recursively, but preserve certain files
            if (!$this->copyDirectory($source, $destDir)) {
                return ['success' => false, 'message' => "Failed to copy $srcDir"];
            }
        }

        // Update version.php
        $versionSource = $extractedFolder . '/public/version.php';
        $versionDest = $publicRoot . '/version.php';
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
            
            // Skip themes and config directory entirely
            if (strpos($targetPath, '/resources/views/themes') !== false ||
                strpos($targetPath, '/assets/themes') !== false ||
                strpos($targetPath, '/resources/config') !== false) {
                continue;
            }
            
            if ($file->isDir()) {
                if (!is_dir($targetPath)) {
                    mkdir($targetPath, 0755, true);
                }
            } else {
                // Skip config files and uploads
                if (strpos($targetPath, '/config/settings.php') !== false ||
                    strpos($targetPath, '/assets/images/uploads/') !== false) {
                    continue;
                }
                
                $dir = dirname($targetPath);
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
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

        // Step 3: Backup
        $backupResult = $this->createBackup();
        if (!$backupResult['success']) {
            @unlink($downloadResult['zip_file']);
            $this->removeDirectory($extractResult['extracted_folder']);
            return $backupResult;
        }

        // Step 4: Install
        $installResult = $this->installUpdate($extractResult['extracted_folder']);
        
        // Cleanup
        @unlink($downloadResult['zip_file']);
        $this->removeDirectory($extractResult['extracted_folder']);

        if ($installResult['success']) {
            $installResult['backup_file'] = $backupResult['backup_file'];
        }

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
