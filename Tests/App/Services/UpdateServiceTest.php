<?php

declare(strict_types=1);

namespace Tests\App\Services;

use App\Services\UpdateFilesystem;
use App\Services\UpdateService;
use PHPUnit\Framework\TestCase;

/**
 * Full test suite for UpdateService.
 *
 * Design goals
 * ────────────
 * • Zero real network calls  – every HTTP method is overridden in anonymous subclasses.
 * • Zero real filesystem side-effects outside sys_get_temp_dir().
 * • Each test is self-contained and cleans up after itself.
 * • Coverage target: every public + protected method, every branch.
 */
final class UpdateServiceTest extends TestCase
{
    // =========================================================================
    //  Helpers
    // =========================================================================

    /** Creates a throw-away DOCUMENT_ROOT and returns its path. */
    private function makeTempRoot(string $prefix = 'upd'): string
    {
        $dir = sys_get_temp_dir() . '/' . $prefix . '-' . uniqid();
        mkdir($dir, 0777, true);
        return $dir;
    }

    /** Writes a version.php inside $root. */
    private function writeVersionFile(string $root, string $version): void
    {
        file_put_contents(
            $root . '/version.php',
            '<?php const _CORE_VERSION = "' . $version . '";'
        );
    }

    /**
     * Returns a stub that overrides fetchUrl() so no real HTTP call is made.
     *
     * @param array<string,string|null> $map  url => body (null = simulate failure)
     */
    private function stubFetch(array $map): UpdateService
    {
        return new class($map) extends UpdateService {
            public function __construct(private array $map) {}
            protected function fetchUrl(string $url, int $timeout = 10): ?string
            {
                return $this->map[$url] ?? null;
            }
        };
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

    /** @param array<string,string> $entries */
    private function createZip(array $entries): string
    {
        $zipPath = tempnam(sys_get_temp_dir(), 'upd-zip-');
        $zip = new \ZipArchive();
        $zip->open($zipPath, \ZipArchive::OVERWRITE);
        foreach ($entries as $name => $content) {
            $zip->addFromString($name, $content);
        }
        $zip->close();

        return $zipPath;
    }

    // =========================================================================
    //  getCurrentVersion()
    // =========================================================================

    public function testGetCurrentVersionReadsFromVersionFile(): void
    {
        $root = $this->makeTempRoot();
        $_SERVER['DOCUMENT_ROOT'] = $root;
        $this->writeVersionFile($root, '3.0.0');

        $svc = $this->stubFetch([]);
        $this->assertSame('3.0.0', $svc->getCurrentVersion());
    }

    public function testGetCurrentVersionReturnsSingleDigitVersion(): void
    {
        $root = $this->makeTempRoot();
        $_SERVER['DOCUMENT_ROOT'] = $root;
        $this->writeVersionFile($root, '1.0.0');

        $svc = $this->stubFetch([]);
        $this->assertSame('1.0.0', $svc->getCurrentVersion());
    }

    public function testGetCurrentVersionReturnsUnknownWhenFileMissing(): void
    {
        $_SERVER['DOCUMENT_ROOT'] = $this->makeTempRoot('missing');

        $svc = $this->stubFetch([]);
        $this->assertSame('Unknown', $svc->getCurrentVersion());
    }

    public function testGetCurrentVersionReturnsUnknownWhenFileHasNoVersionConstant(): void
    {
        $root = $this->makeTempRoot();
        $_SERVER['DOCUMENT_ROOT'] = $root;
        file_put_contents($root . '/version.php', '<?php // no constant here');

        $svc = $this->stubFetch([]);
        $this->assertSame('Unknown', $svc->getCurrentVersion());
    }

    public function testGetCurrentVersionReturnsUnknownWhenFileReadFails(): void
    {
        $dir = $this->makeTempRoot('verdir');

        $svc = new class($dir) extends UpdateService {
            public function __construct(private string $path) {}
            protected function getVersionFilePath(): string { return $this->path; }
        };

        $this->assertSame('Unknown', $svc->getCurrentVersion());
    }

    public function testGetCurrentVersionAcceptsSingleQuoteDelimiter(): void
    {
        $root = $this->makeTempRoot();
        $_SERVER['DOCUMENT_ROOT'] = $root;
        file_put_contents($root . '/version.php', "<?php const _CORE_VERSION = '2.5.1';");

        $svc = $this->stubFetch([]);
        $this->assertSame('2.5.1', $svc->getCurrentVersion());
    }

    public function testGetCurrentVersionAcceptsSpacesAroundEquals(): void
    {
        $root = $this->makeTempRoot();
        $_SERVER['DOCUMENT_ROOT'] = $root;
        file_put_contents($root . '/version.php', '<?php const _CORE_VERSION  =  "4.1.0";');

        $svc = $this->stubFetch([]);
        $this->assertSame('4.1.0', $svc->getCurrentVersion());
    }

    // =========================================================================
    //  getRemoteVersion()
    // =========================================================================

    public function testGetRemoteVersionParsesValidResponse(): void
    {
        $repoUrl = 'https://raw.githubusercontent.com/librixsoft/balerocms/development/public/version.php';
        $body    = '<?php const _CORE_VERSION = "5.0.1";';

        $svc = $this->stubFetch([$repoUrl => $body]);
        $this->assertSame('5.0.1', $svc->getRemoteVersion());
    }

    public function testGetRemoteVersionReturnsNullOnNetworkFailure(): void
    {
        $svc = $this->stubFetch([]); // all URLs return null
        $this->assertNull($svc->getRemoteVersion());
    }

    public function testGetRemoteVersionReturnsNullWhenBodyHasNoVersionConstant(): void
    {
        $repoUrl = 'https://raw.githubusercontent.com/librixsoft/balerocms/development/public/version.php';

        $svc = $this->stubFetch([$repoUrl => '<?php // empty file']);
        $this->assertNull($svc->getRemoteVersion());
    }

    public function testGetRemoteVersionReturnsNullOnEmptyBody(): void
    {
        $repoUrl = 'https://raw.githubusercontent.com/librixsoft/balerocms/development/public/version.php';

        $svc = $this->stubFetch([$repoUrl => '']);
        $this->assertNull($svc->getRemoteVersion());
    }

    public function testGetRemoteVersionAcceptsSingleQuotesAndSpaces(): void
    {
        $repoUrl = 'https://raw.githubusercontent.com/librixsoft/balerocms/development/public/version.php';
        $body    = "<?php  const   _CORE_VERSION  =  '6.1.0' ;";

        $svc = $this->stubFetch([$repoUrl => $body]);
        $this->assertSame('6.1.0', $svc->getRemoteVersion());
    }

    // =========================================================================
    //  isUpdateAvailable()
    // =========================================================================

    public function testIsUpdateAvailableTrueWhenRemoteIsNewer(): void
    {
        $root = $this->makeTempRoot();
        $_SERVER['DOCUMENT_ROOT'] = $root;
        $this->writeVersionFile($root, '1.2.3');

        $svc = new class extends UpdateService {
            public function getRemoteVersion(): ?string { return '1.2.4'; }
        };

        $info = $svc->isUpdateAvailable();
        $this->assertTrue($info['update_available']);
        $this->assertSame('1.2.3', $info['current_version']);
        $this->assertSame('1.2.4', $info['remote_version']);
        $this->assertNotEmpty($info['repo_url']);
    }

    public function testIsUpdateAvailableFalseWhenSameVersion(): void
    {
        $root = $this->makeTempRoot();
        $_SERVER['DOCUMENT_ROOT'] = $root;
        $this->writeVersionFile($root, '2.0.0');

        $svc = new class extends UpdateService {
            public function getRemoteVersion(): ?string { return '2.0.0'; }
        };

        $info = $svc->isUpdateAvailable();
        $this->assertFalse($info['update_available']);
    }

    public function testIsUpdateAvailableFalseWhenRemoteIsOlder(): void
    {
        $root = $this->makeTempRoot();
        $_SERVER['DOCUMENT_ROOT'] = $root;
        $this->writeVersionFile($root, '2.0.0');

        $svc = new class extends UpdateService {
            public function getRemoteVersion(): ?string { return '1.9.9'; }
        };

        $info = $svc->isUpdateAvailable();
        $this->assertFalse($info['update_available']);
        $this->assertSame('1.9.9', $info['remote_version']);
    }

    public function testIsUpdateAvailableFalseWhenCurrentVersionIsUnknown(): void
    {
        $_SERVER['DOCUMENT_ROOT'] = $this->makeTempRoot('no-ver');

        $svc = new class extends UpdateService {
            public function getRemoteVersion(): ?string { return '9.9.9'; }
        };

        $info = $svc->isUpdateAvailable();
        $this->assertFalse($info['update_available']);
        $this->assertSame('Unknown', $info['current_version']);
    }

    public function testIsUpdateAvailableFalseWhenRemoteVersionIsNull(): void
    {
        $root = $this->makeTempRoot();
        $_SERVER['DOCUMENT_ROOT'] = $root;
        $this->writeVersionFile($root, '1.0.0');

        $svc = new class extends UpdateService {
            public function getRemoteVersion(): ?string { return null; }
        };

        $info = $svc->isUpdateAvailable();
        $this->assertFalse($info['update_available']);
        $this->assertSame('Unknown', $info['remote_version']);
    }

    public function testIsUpdateAvailableContainsRepoUrl(): void
    {
        $root = $this->makeTempRoot();
        $_SERVER['DOCUMENT_ROOT'] = $root;
        $this->writeVersionFile($root, '1.0.0');

        $svc = new class extends UpdateService {
            public function getRemoteVersion(): ?string { return null; }
        };

        $info = $svc->isUpdateAvailable();
        $this->assertArrayHasKey('repo_url', $info);
        $this->assertStringStartsWith('https://', $info['repo_url']);
    }

    // =========================================================================
    //  selfUpdate()
    // =========================================================================

    public function testSelfUpdateSuccessWritesNewContent(): void
    {
        $target = tempnam(sys_get_temp_dir(), 'self-upd-');

        $svc = new class($target) extends UpdateService {
            public function __construct(private string $target) {}
            protected function fetchUrl(string $url, int $timeout = 10): ?string
            {
                return '<?php // new version';
            }
            protected function getSelfFilePath(): string { return $this->target; }
        };

        $result = $svc->selfUpdate();
        $this->assertTrue($result['success']);
        $this->assertStringContainsString('self-updated', $result['message']);
        $this->assertSame('<?php // new version', file_get_contents($target));

        $supportPath = dirname($target) . '/UpdateFilesystem.php';
        $this->assertFileExists($supportPath);
        $this->assertSame('<?php // new version', file_get_contents($supportPath));

        @unlink($target);
        @unlink($supportPath);
    }

    public function testSelfUpdateFailsWhenFetchFails(): void
    {
        $svc = new class extends UpdateService {
            protected function fetchUrl(string $url, int $timeout = 10): ?string { return null; }
        };

        $result = $svc->selfUpdate();
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Failed to download', $result['message']);
    }

    public function testSelfUpdateFailsWhenWriteFails(): void
    {
        $svc = new class extends UpdateService {
            protected function fetchUrl(string $url, int $timeout = 10): ?string { return '<?php echo 1;'; }
            protected function getSelfFilePath(): string { return '/nonexistent-dir/file.php'; }
        };

        $result = $svc->selfUpdate();
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Failed to write', $result['message']);
    }

    // =========================================================================
    //  downloadUpdate()
    // =========================================================================

    public function testDownloadUpdateSuccessCreatesZipFile(): void
    {
        $tempDir = sys_get_temp_dir();
        $zipPath = $tempDir . '/balerocms-update.zip';
        @unlink($zipPath); // clean slate

        $svc = new class($tempDir) extends UpdateService {
            public function __construct(private string $td) {}
            protected function getTempDir(): string { return $this->td; }
            protected function fetchUrl(string $url, int $timeout = 10): ?string { return 'ZIPDATA'; }
        };

        $result = $svc->downloadUpdate();
        $this->assertTrue($result['success']);
        $this->assertFileExists($result['zip_file']);
        $this->assertSame('ZIPDATA', file_get_contents($result['zip_file']));

        @unlink($result['zip_file']);
    }

    public function testDownloadUpdateFailsOnNetworkError(): void
    {
        $svc = new class extends UpdateService {
            protected function fetchUrl(string $url, int $timeout = 10): ?string { return null; }
        };

        $result = $svc->downloadUpdate();
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Failed to download', $result['message']);
    }

    public function testDownloadUpdateFailsWhenCannotWriteZip(): void
    {
        $svc = new class extends UpdateService {
            protected function getTempDir(): string { return '/nonexistent-dir'; }
            protected function fetchUrl(string $url, int $timeout = 10): ?string { return 'DATA'; }
        };

        $result = $svc->downloadUpdate();
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Failed to save', $result['message']);
    }

    // =========================================================================
    //  extractUpdate()
    // =========================================================================

    public function testValidateZipContentsRejectsUnsafeEntryName(): void
    {
        $zipPath = $this->createZip(['../evil.txt' => 'x']);

        $svc = new class extends UpdateService {
            public function validate(\ZipArchive $zip): array
            {
                return $this->validateZipContents($zip);
            }
        };

        $zip = new \ZipArchive();
        $zip->open($zipPath);
        $result = $svc->validate($zip);
        $zip->close();

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('unsafe', $result['message']);

        @unlink($zipPath);
    }

    public function testValidateZipContentsRejectsTooManyFiles(): void
    {
        $zipPath = tempnam(sys_get_temp_dir(), 'upd-zip-many-');
        $zip = new \ZipArchive();
        $zip->open($zipPath, \ZipArchive::OVERWRITE);
        for ($i = 0; $i < 5001; $i++) {
            $zip->addFromString('file-' . $i . '.txt', 'x');
        }
        $zip->close();

        $svc = new class extends UpdateService {
            public function validate(\ZipArchive $zip): array
            {
                return $this->validateZipContents($zip);
            }
        };

        $zip = new \ZipArchive();
        $zip->open($zipPath);
        $result = $svc->validate($zip);
        $zip->close();

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('too many files', $result['message']);

        @unlink($zipPath);
    }

    public function testValidateZipContentsRejectsOversizedZip(): void
    {
        $largeFile = tempnam(sys_get_temp_dir(), 'upd-large-');
        $handle = fopen($largeFile, 'wb');
        ftruncate($handle, 201 * 1024 * 1024);
        fclose($handle);

        $zipPath = tempnam(sys_get_temp_dir(), 'upd-zip-large-');
        $zip = new \ZipArchive();
        $zip->open($zipPath, \ZipArchive::OVERWRITE);
        $zip->addFile($largeFile, 'large.bin');
        $zip->close();

        $svc = new class extends UpdateService {
            public function validate(\ZipArchive $zip): array
            {
                return $this->validateZipContents($zip);
            }
        };

        $zip = new \ZipArchive();
        $zip->open($zipPath);
        $result = $svc->validate($zip);
        $zip->close();

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('maximum allowed size', $result['message']);

        @unlink($zipPath);
        @unlink($largeFile);
    }

    public function testOpenAndExtractZipRejectsUnsafeZip(): void
    {
        $zipPath = $this->createZip(['../evil.txt' => 'x']);
        $dest = $this->makeTempRoot('zip-dest');

        $svc = new class extends UpdateService {
            public function openAndExtract(string $zipFile, string $destDir): array
            {
                return $this->openAndExtractZip($zipFile, $destDir);
            }
        };

        $result = $svc->openAndExtract($zipPath, $dest);
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('unsafe', $result['message']);

        @unlink($zipPath);
        $this->makeFilesystem()->removeDirectory($dest);
    }

    public function testOpenAndExtractZipExtractsOnlyValidatedFiles(): void
    {
        $zipPath = $this->createZip([
            'safe/a.txt' => 'a',
            'safe/b.txt' => 'b',
        ]);
        $dest = $this->makeTempRoot('zip-ok');

        $svc = new class extends UpdateService {
            public function openAndExtract(string $zipFile, string $destDir): array
            {
                return $this->openAndExtractZip($zipFile, $destDir);
            }
        };

        $result = $svc->openAndExtract($zipPath, $dest);
        $this->assertTrue($result['success']);
        $this->assertFileExists($dest . '/safe/a.txt');
        $this->assertFileExists($dest . '/safe/b.txt');

        @unlink($zipPath);
        $this->makeFilesystem()->removeDirectory($dest);
    }

    public function testExtractUpdateFailsWhenZipExtensionUnavailable(): void
    {
        $svc = new class extends UpdateService {
            protected function isZipAvailable(): bool { return false; }
        };

        $result = $svc->extractUpdate('/any/file.zip');
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('ZipArchive', $result['message']);
    }

    public function testExtractUpdateFailsWhenOpenZipFails(): void
    {
        $svc = new class extends UpdateService {
            protected function isZipAvailable(): bool { return true; }
            protected function openAndExtractZip(string $zipFile, string $destDir): array
            {
                return ['success' => false, 'message' => 'Failed to open ZIP file'];
            }
        };

        $result = $svc->extractUpdate('/any/file.zip');
        $this->assertFalse($result['success']);
        $this->assertSame('Failed to open ZIP file', $result['message']);
    }

    public function testExtractUpdateFailsWhenExtractFails(): void
    {
        $svc = new class extends UpdateService {
            protected function isZipAvailable(): bool { return true; }
            protected function openAndExtractZip(string $zipFile, string $destDir): array
            {
                return ['success' => false, 'message' => 'Failed to extract ZIP file'];
            }
        };

        $result = $svc->extractUpdate('/any/file.zip');
        $this->assertFalse($result['success']);
        $this->assertSame('Failed to extract ZIP file', $result['message']);
    }

    public function testExtractUpdateFailsWhenExtractedFolderMissing(): void
    {
        $tempDir = $this->makeTempRoot('ext');

        $svc = new class($tempDir) extends UpdateService {
            public function __construct(private string $td) {}
            protected function isZipAvailable(): bool { return true; }
            protected function getTempDir(): string { return $this->td; }
            protected function openAndExtractZip(string $zipFile, string $destDir): array
            {
                // Creates destDir but NOT the expected sub-folder
                @mkdir($destDir, 0777, true);
                return ['success' => true];
            }
        };

        $result = $svc->extractUpdate('/fake.zip');
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Extracted folder not found', $result['message']);
    }

    public function testExtractUpdateSuccessReturnsExtractedFolderPath(): void
    {
        $tempDir = $this->makeTempRoot('ext-ok');

        $svc = new class($tempDir) extends UpdateService {
            public function __construct(private string $td) {}
            protected function isZipAvailable(): bool { return true; }
            protected function getTempDir(): string { return $this->td; }
            protected function openAndExtractZip(string $zipFile, string $destDir): array
            {
                @mkdir($destDir . '/balerocms-development', 0777, true);
                return ['success' => true];
            }
        };

        $result = $svc->extractUpdate('/fake.zip');
        $this->assertTrue($result['success']);
        $this->assertStringEndsWith('balerocms-development', $result['extracted_folder']);
    }

    // =========================================================================
    //  installUpdate()
    // =========================================================================

    public function testInstallUpdateCopiesDirectoriesToDestination(): void
    {
        $extracted = $this->makeTempRoot('extracted');
        $root      = $this->makeTempRoot('docroot');
        $_SERVER['DOCUMENT_ROOT'] = $root;

        // Create a fake "App" dir with a PHP file inside the extracted folder
        mkdir($extracted . '/App', 0777, true);
        file_put_contents($extracted . '/App/MyClass.php', '<?php class MyClass {}');

        $installRoot = $this->makeTempRoot('install-root');

        $svc = new class($installRoot) extends UpdateService {
            public function __construct(private string $base) {}
            protected function getRootPath(): string { return $this->base; }
            // Only install "App" to keep the test focused
            protected array $dirsToUpdate = ['App'];
        };

        $result = $svc->installUpdate($extracted);
        $this->assertTrue($result['success']);
        $this->assertFileExists($installRoot . '/App/MyClass.php');
    }

    public function testInstallUpdateCopiesPublicToDocumentRoot(): void
    {
        $extracted = $this->makeTempRoot('ext-public');
        $docRoot   = $this->makeTempRoot('docroot-public');
        $_SERVER['DOCUMENT_ROOT'] = $docRoot;

        mkdir($extracted . '/public', 0777, true);
        file_put_contents($extracted . '/public/index.php', '<?php echo "ok";');

        $installRoot = $this->makeTempRoot('install-public-root');

        $svc = new class($installRoot) extends UpdateService {
            public function __construct(private string $base) {}
            protected function getRootPath(): string { return $this->base; }
            protected array $dirsToUpdate = ['public'];
        };

        $result = $svc->installUpdate($extracted);
        $this->assertTrue($result['success']);
        $this->assertFileExists($docRoot . '/index.php');
        $this->assertFileDoesNotExist($installRoot . '/public/index.php');
    }

    public function testInstallUpdateRefreshesVersionFile(): void
    {
        $extracted = $this->makeTempRoot('ext-ver');
        $root      = $this->makeTempRoot('docroot-ver');
        $_SERVER['DOCUMENT_ROOT'] = $root;

        mkdir($extracted . '/public', 0777, true);
        file_put_contents($extracted . '/public/version.php', '<?php const _CORE_VERSION = "9.9.9";');

        $svc = new class extends UpdateService {
            protected array $dirsToUpdate = []; // skip dir copy, only refresh version
        };

        $result = $svc->installUpdate($extracted);
        $this->assertTrue($result['success']);
        $this->assertFileExists($root . '/version.php');
        $content = file_get_contents($root . '/version.php');
        $this->assertStringContainsString('9.9.9', $content);
    }

    public function testInstallUpdateSkipsMissingSourceDirectories(): void
    {
        $extracted = $this->makeTempRoot('ext-skip');
        $root      = $this->makeTempRoot('docroot-skip');
        $_SERVER['DOCUMENT_ROOT'] = $root;

        // "App" folder deliberately absent → should be silently skipped
        $svc = new class extends UpdateService {
            protected array $dirsToUpdate = ['App'];
        };

        $result = $svc->installUpdate($extracted);
        $this->assertTrue($result['success']);
    }

    public function testInstallUpdateReturnsErrorWhenCopyFails(): void
    {
        $extracted = $this->makeTempRoot('ext-fail');
        $root      = $this->makeTempRoot('docroot-fail');
        $_SERVER['DOCUMENT_ROOT'] = $root;

        mkdir($extracted . '/App', 0777, true);
        file_put_contents($extracted . '/App/Dummy.php', '<?php');

        $svc = new class extends UpdateService {
            protected array $dirsToUpdate = ['App'];
            protected function getFilesystem(): UpdateFilesystem
            {
                return new class extends UpdateFilesystem {
                    public function __construct()
                    {
                        parent::__construct([
                            '/resources/config/balero.config.json',
                            '/assets/images/uploads/',
                            '/resources/views/themes/',
                            '/resources/config/',
                            '/favicon.ico',
                        ]);
                    }
                    public function copyDirectory(string $source, string $destination): bool { return false; }
                };
            }
        };

        $result = $svc->installUpdate($extracted);
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('App', $result['message']);
    }

    // =========================================================================
    //  performUpdate()
    // =========================================================================

    public function testPerformUpdateFailsAtDownloadStep(): void
    {
        $svc = new class extends UpdateService {
            public function downloadUpdate(): array { return ['success' => false, 'message' => 'network error']; }
        };

        $result = $svc->performUpdate();
        $this->assertFalse($result['success']);
        $this->assertSame('network error', $result['message']);
    }

    public function testPerformUpdateFailsAtExtractStepAndDeletesZip(): void
    {
        $zipFile = tempnam(sys_get_temp_dir(), 'zip-');
        file_put_contents($zipFile, 'data');

        $svc = new class($zipFile) extends UpdateService {
            public function __construct(private string $z) {}
            public function downloadUpdate(): array { return ['success' => true, 'zip_file' => $this->z]; }
            public function extractUpdate(string $zipFile): array { return ['success' => false, 'message' => 'bad zip']; }
        };

        $result = $svc->performUpdate();
        $this->assertFalse($result['success']);
        $this->assertSame('bad zip', $result['message']);
        $this->assertFileDoesNotExist($zipFile);
    }

    public function testPerformUpdateSuccessAndCleansArtifacts(): void
    {
        $zipFile = tempnam(sys_get_temp_dir(), 'zip-ok-');
        file_put_contents($zipFile, 'data');

        $extractedDir = $this->makeTempRoot('extracted-ok');
        file_put_contents($extractedDir . '/marker.txt', 'x');

        $svc = new class($zipFile, $extractedDir) extends UpdateService {
            public function __construct(private string $z, private string $e) {}
            public function downloadUpdate(): array { return ['success' => true, 'zip_file' => $this->z]; }
            public function extractUpdate(string $zipFile): array { return ['success' => true, 'extracted_folder' => $this->e]; }
            public function installUpdate(string $extractedFolder): array { return ['success' => true, 'message' => 'installed']; }
        };

        $result = $svc->performUpdate();
        $this->assertTrue($result['success']);
        $this->assertSame('installed', $result['message']);
        $this->assertFileDoesNotExist($zipFile);
        $this->assertDirectoryDoesNotExist($extractedDir);
    }

    public function testPerformUpdateInstallFailureCleansArtifacts(): void
    {
        $zipFile = tempnam(sys_get_temp_dir(), 'zip-fail-');
        file_put_contents($zipFile, 'data');

        $extractedDir = $this->makeTempRoot('extracted-fail');

        $svc = new class($zipFile, $extractedDir) extends UpdateService {
            public function __construct(private string $z, private string $e) {}
            public function downloadUpdate(): array { return ['success' => true, 'zip_file' => $this->z]; }
            public function extractUpdate(string $zipFile): array { return ['success' => true, 'extracted_folder' => $this->e]; }
            public function installUpdate(string $extractedFolder): array { return ['success' => false, 'message' => 'install failed']; }
        };

        $result = $svc->performUpdate();
        $this->assertFalse($result['success']);
        $this->assertSame('install failed', $result['message']);
        $this->assertFileDoesNotExist($zipFile);
        $this->assertDirectoryDoesNotExist($extractedDir);
    }

    // =========================================================================
    //  copyDirectory()
    // =========================================================================

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
        $dst = sys_get_temp_dir() . '/dst-new-' . uniqid(); // does NOT exist yet

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

    // =========================================================================
    //  isProtectedPath()
    // =========================================================================

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

    // =========================================================================
    //  removeDirectory()
    // =========================================================================

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
        // Should not throw
        $fs->removeDirectory('/this/does/not/exist-' . uniqid());
        $this->assertTrue(true); // reached without exception
    }

    public function testRemoveDirectoryHandlesEmptyDirectory(): void
    {
        $dir = $this->makeTempRoot('rm-empty');
        $fs = $this->makeFilesystem();
        $fs->removeDirectory($dir);
        $this->assertDirectoryDoesNotExist($dir);
    }

    // =========================================================================
    //  Edge-cases / cross-cutting
    // =========================================================================

    public function testVersionComparisonHandlesPreReleaseLabels(): void
    {
        $root = $this->makeTempRoot();
        $_SERVER['DOCUMENT_ROOT'] = $root;
        $this->writeVersionFile($root, '1.0.0');

        // PHP's version_compare treats '1.0.0-alpha' < '1.0.0', so no update expected
        $svc = new class extends UpdateService {
            public function getRemoteVersion(): ?string { return '1.0.0-alpha'; }
        };

        $info = $svc->isUpdateAvailable();
        $this->assertFalse($info['update_available']);
    }

    public function testInstallUpdateWithMultipleDirsAllSucceed(): void
    {
        $extracted = $this->makeTempRoot('multi-src');
        $root      = $this->makeTempRoot('multi-root');
        $_SERVER['DOCUMENT_ROOT'] = $root;

        foreach (['App', 'Framework'] as $dir) {
            mkdir($extracted . '/' . $dir, 0777, true);
            file_put_contents($extracted . '/' . $dir . '/File.php', '<?php');
        }

        $installRoot = $this->makeTempRoot('multi-install');

        $svc = new class($installRoot) extends UpdateService {
            public function __construct(private string $base) {}
            protected function getRootPath(): string { return $this->base; }
            protected array $dirsToUpdate = ['App', 'Framework'];
        };

        $result = $svc->installUpdate($extracted);
        $this->assertTrue($result['success']);
        $this->assertFileExists($installRoot . '/App/File.php');
        $this->assertFileExists($installRoot . '/Framework/File.php');
    }

    public function testPerformUpdateFullPipelineIntegration(): void
    {
        // Simulate full pipeline: download → extract → install → cleanup
        $zipFile      = tempnam(sys_get_temp_dir(), 'int-zip-');
        $extractedDir = $this->makeTempRoot('int-ext');
        file_put_contents($zipFile, 'data');

        // Use an ArrayObject so the anonymous class can mutate it without
        // needing a by-reference constructor parameter (not allowed in PHP).
        $callOrder = new \ArrayObject();

        $svc = new class($zipFile, $extractedDir, $callOrder) extends UpdateService {
            public function __construct(
                private string $z,
                private string $e,
                private \ArrayObject $order
            ) {}
            public function downloadUpdate(): array
            {
                $this->order->append('download');
                return ['success' => true, 'zip_file' => $this->z];
            }
            public function extractUpdate(string $zipFile): array
            {
                $this->order->append('extract');
                return ['success' => true, 'extracted_folder' => $this->e];
            }
            public function installUpdate(string $extractedFolder): array
            {
                $this->order->append('install');
                return ['success' => true, 'message' => 'ok'];
            }
        };

        $result = $svc->performUpdate();
        $this->assertTrue($result['success']);
        $this->assertSame(['download', 'extract', 'install'], $callOrder->getArrayCopy());
        $this->assertFileDoesNotExist($zipFile);
    }
}