<?php

namespace App\Services;

use Framework\Attributes\Service;

#[Service]
class UpdateService
{
    private const REPO_URL        = 'https://raw.githubusercontent.com/librixsoft/balerocms/development/public/version.php';
    private const GITHUB_REPO     = 'https://github.com/librixsoft/balerocms/tree/development';
    private const SERVICE_URL     = 'https://raw.githubusercontent.com/librixsoft/balerocms/development/App/Services/UpdateService.php';
    private const ZIP_URL         = 'https://github.com/librixsoft/balerocms/archive/refs/heads/development.zip';
    private const EXTRACTED_NAME  = 'balerocms-development';
    private const MAX_ZIP_FILES   = 5000;
    private const MAX_ZIP_BYTES   = 200 * 1024 * 1024; // 200MB

    /** @var string[] Directories copied during install */
    protected array $dirsToUpdate = ['App', 'Framework', 'public', 'resources'];

    /** @var string[] Relative paths that must never be overwritten */
    protected array $protectedPaths = [
        '/resources/config/balero.config.json',
        '/assets/images/uploads/',
        '/resources/views/themes/',
        '/resources/config/',
        '/favicon.ico',
    ];

    private ?UpdateFilesystem $filesystem = null;

    // -------------------------------------------------------------------------
    //  Version helpers
    // -------------------------------------------------------------------------

    public function getCurrentVersion(): string
    {
        $version = null;

        if (defined('_CORE_VERSION')) {
            $version = _CORE_VERSION;
        } else {
            $path = $this->getVersionFilePath();

            if (is_file($path) && is_readable($path)) {
                $content = file_get_contents($path);
                if ($content !== false) {
                    $version = $this->parseVersionFromContent($content);
                }
            }
        }

        return $version ?? 'Unknown';
    }

    /** Extracts _CORE_VERSION from a file body, or null if not present. */
    protected function parseVersionFromContent(string $content): ?string
    {
        if ($content === '') {
            return null;
        }

        if (preg_match('/_CORE_VERSION\s*=\s*["\']([^"\']+)["\']/', $content, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /** Overridable so tests can inject a custom path. */
    protected function getVersionFilePath(): string
    {
        return $_SERVER['DOCUMENT_ROOT'] . '/version.php';
    }

    /** Overridable so tests can return a fixed base path. */
    protected function getRootPath(): string
    {
        return defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 2);
    }

    /** Overridable so tests can return a fixed temp dir. */
    protected function getTempDir(): string
    {
        return sys_get_temp_dir();
    }

    public function getRemoteVersion(): ?string
    {
        $content = $this->fetchUrl(self::REPO_URL);
        if ($content === null) {
            return null;
        }

        return $this->parseVersionFromContent($content);
    }

    public function isUpdateAvailable(): array
    {
        $current = $this->getCurrentVersion();
        $remote  = $this->getRemoteVersion();

        $updateAvailable = $remote !== null
            && $current !== 'Unknown'
            && version_compare($remote, $current, '>');

        return [
            'current_version'  => $current,
            'remote_version'   => $remote ?? 'Unknown',
            'update_available' => $updateAvailable,
            'repo_url'         => self::GITHUB_REPO,
        ];
    }

    // -------------------------------------------------------------------------
    //  Self-update
    // -------------------------------------------------------------------------

    public function selfUpdate(): array
    {
        $content = $this->fetchUrl(self::SERVICE_URL, 15);
        if ($content === null) {
            return ['success' => false, 'message' => 'Failed to download UpdateService.php from repo'];
        }

        $selfPath = $this->getSelfFilePath();
        $selfDir  = dirname($selfPath);
        if (!is_dir($selfDir) || !is_writable($selfDir)) {
            return ['success' => false, 'message' => 'Failed to write UpdateService.php'];
        }

        if (@file_put_contents($selfPath, $content) === false) {
            return ['success' => false, 'message' => 'Failed to write UpdateService.php'];
        }

        return ['success' => true, 'message' => 'UpdateService.php self-updated successfully'];
    }

    /** Overridable so tests can redirect writes away from the real file. */
    protected function getSelfFilePath(): string
    {
        return __FILE__;
    }

    // -------------------------------------------------------------------------
    //  Download
    // -------------------------------------------------------------------------

    public function downloadUpdate(): array
    {
        $tempDir = $this->getTempDir();
        $zipFile = $tempDir . '/balerocms-update.zip';
        $content = $this->fetchUrl(self::ZIP_URL, 300);

        if ($content === null) {
            return ['success' => false, 'message' => 'Failed to download update'];
        }

        if (!is_dir($tempDir) || !is_writable($tempDir)) {
            return ['success' => false, 'message' => 'Failed to save update file'];
        }

        if (@file_put_contents($zipFile, $content) === false) {
            return ['success' => false, 'message' => 'Failed to save update file'];
        }

        return ['success' => true, 'zip_file' => $zipFile];
    }

    // -------------------------------------------------------------------------
    //  Extract
    // -------------------------------------------------------------------------

    public function extractUpdate(string $zipFile): array
    {
        if (!$this->isZipAvailable()) {
            return ['success' => false, 'message' => 'ZipArchive extension is not available'];
        }

        $tempDir = $this->getTempDir() . '/balerocms-update-' . time();
        $result  = $this->openAndExtractZip($zipFile, $tempDir);

        if (!$result['success']) {
            return $result;
        }

        $extractedFolder = $tempDir . '/' . self::EXTRACTED_NAME;

        if (!is_dir($extractedFolder)) {
            return ['success' => false, 'message' => 'Extracted folder not found'];
        }

        return ['success' => true, 'extracted_folder' => $extractedFolder];
    }

    /** Thin wrapper so tests can stub ZipArchive availability. */
    protected function isZipAvailable(): bool
    {
        return class_exists('ZipArchive');
    }

    /** Thin wrapper so tests can stub the actual ZIP open/extract. */
    protected function openAndExtractZip(string $zipFile, string $destDir): array
    {
        $zip = new \ZipArchive();
        $result = ['success' => true];

        if ($zip->open($zipFile) !== true) {
            $result = ['success' => false, 'message' => 'Failed to open ZIP file'];
        } else {
            $sizeCheck = $this->validateZipContents($zip);
            if (!$sizeCheck['success']) {
                $result = $sizeCheck;
            } else {
                // Sonar: validated file count, total size, and safe entry paths before extraction.
                if (!$zip->extractTo($destDir, $sizeCheck['files'])) { // NOSONAR
                    $result = ['success' => false, 'message' => 'Failed to extract ZIP file'];
                }
            }
        }

        $zip->close();
        return $result;
    }

    /**
     * Validate ZIP contents to avoid zip-bomb style resource exhaustion and zip-slip paths.
     *
     * @return array{success: bool, message?: string, files?: string[]}
     */
    protected function validateZipContents(\ZipArchive $zip): array
    {
        $totalBytes = 0;
        $fileCount  = $zip->numFiles ?? 0;
        $result = ['success' => true, 'files' => []];

        if ($fileCount > self::MAX_ZIP_FILES) {
            $result = ['success' => false, 'message' => 'ZIP contains too many files'];
        } else {
            for ($i = 0; $i < $fileCount; $i++) {
                $stat = $zip->statIndex($i);
                if ($stat === false) {
                    $result = ['success' => false, 'message' => 'Failed to read ZIP file entries'];
                    break;
                }

                $name = (string) ($stat['name'] ?? '');
                if (!$this->isSafeZipEntryName($name)) {
                    $result = ['success' => false, 'message' => 'ZIP contains unsafe file paths'];
                    break;
                }

                $entrySize = (int) ($stat['size'] ?? 0);
                $totalBytes += $entrySize;

                if ($totalBytes > self::MAX_ZIP_BYTES) {
                    $result = ['success' => false, 'message' => 'ZIP exceeds maximum allowed size'];
                    break;
                }

                $result['files'][] = $name;
            }
        }

        return $result;
    }

    private function isSafeZipEntryName(string $name): bool
    {
        if ($name === '' || str_starts_with($name, '/')) {
            return false;
        }

        if (str_contains($name, '\\')) {
            return false;
        }

        return !preg_match('#(^|/)\.\.(?:/|$)#', $name);
    }

    // -------------------------------------------------------------------------
    //  Install
    // -------------------------------------------------------------------------

    public function installUpdate(string $extractedFolder): array
    {
        $rootPath = $this->getRootPath();
        $filesystem = $this->getFilesystem();

        foreach ($this->dirsToUpdate as $dir) {
            $source = $extractedFolder . '/' . $dir;

            $destination = ($dir === 'public')
                ? $_SERVER['DOCUMENT_ROOT']
                : $rootPath . '/' . $dir;

            if (!is_dir($source)) {
                continue;
            }

            if (!$filesystem->copyDirectory($source, $destination)) {
                return ['success' => false, 'message' => "Failed to copy $dir"];
            }
        }

        // Always refresh version.php
        $versionSource = $extractedFolder . '/public/version.php';
        $versionDest   = $_SERVER['DOCUMENT_ROOT'] . '/version.php';
        if (file_exists($versionSource)) {
            copy($versionSource, $versionDest);
        }

        return ['success' => true, 'message' => 'Update installed successfully'];
    }

    // -------------------------------------------------------------------------
    //  Full pipeline
    // -------------------------------------------------------------------------

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

        // Cleanup (always)
        @unlink($downloadResult['zip_file']);
        $this->getFilesystem()->removeDirectory($extractResult['extracted_folder']);

        return $installResult;
    }

    // -------------------------------------------------------------------------
    //  Filesystem helpers
    // -------------------------------------------------------------------------

    protected function getFilesystem(): UpdateFilesystem
    {
        if ($this->filesystem === null) {
            $this->filesystem = new UpdateFilesystem($this->protectedPaths);
        }

        return $this->filesystem;
    }

    // -------------------------------------------------------------------------
    //  HTTP helper (single curl entry-point — easy to stub in tests)
    // -------------------------------------------------------------------------

    /**
     * Fetch a URL via cURL and return the body, or null on failure.
     * Extracted as its own method so subclasses / tests can override it.
     */
    protected function fetchUrl(string $url, int $timeout = 10): ?string
    {
        if (!function_exists('curl_init')) {
            return null;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'BaleroCMS-Updater');
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        $content  = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($content === false || $httpCode !== 200) {
            return null;
        }

        return $content;
    }
}