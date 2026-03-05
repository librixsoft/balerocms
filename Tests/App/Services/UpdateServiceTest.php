<?php

declare(strict_types=1);

namespace Tests\App\Services;

use App\Services\UpdateService;
use PHPUnit\Framework\TestCase;

final class UpdateServiceTest extends TestCase
{
    public function testGetCurrentVersionAndIsUpdateAvailable(): void
    {
        $_SERVER['DOCUMENT_ROOT'] = sys_get_temp_dir() . '/upd-' . uniqid();
        @mkdir($_SERVER['DOCUMENT_ROOT'], 0777, true);
        file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/version.php', '<?php const _CORE_VERSION = "1.2.3";');

        $svc = new class extends UpdateService {
            public function getRemoteVersion(): ?string { return '1.2.4'; }
        };

        $this->assertSame('1.2.3', $svc->getCurrentVersion());
        $info = $svc->isUpdateAvailable();
        $this->assertTrue($info['update_available']);
        $this->assertSame('1.2.4', $info['remote_version']);
    }

    public function testPerformUpdateFailureAtDownloadAndExtract(): void
    {
        $svc1 = new class extends UpdateService {
            public function downloadUpdate(): array { return ['success'=>false,'message'=>'x']; }
        };
        $this->assertFalse($svc1->performUpdate()['success']);

        $svc2 = new class extends UpdateService {
            public function downloadUpdate(): array { $f=tempnam(sys_get_temp_dir(),'z'); file_put_contents($f,'x'); return ['success'=>true,'zip_file'=>$f]; }
            public function extractUpdate(string $zipFile): array { return ['success'=>false,'message'=>'bad']; }
        };
        $this->assertFalse($svc2->performUpdate()['success']);
    }

    public function testGetCurrentVersionReturnsUnknownWhenVersionFileIsMissing(): void
    {
        $_SERVER['DOCUMENT_ROOT'] = sys_get_temp_dir() . '/upd-missing-' . uniqid();
        @mkdir($_SERVER['DOCUMENT_ROOT'], 0777, true);

        $svc = new class extends UpdateService {
            public function getRemoteVersion(): ?string { return null; }
        };

        $this->assertSame('Unknown', $svc->getCurrentVersion());

        $info = $svc->isUpdateAvailable();
        $this->assertFalse($info['update_available']);
        $this->assertSame('Unknown', $info['remote_version']);
    }

    public function testIsUpdateAvailableIsFalseWhenRemoteIsOlder(): void
    {
        $_SERVER['DOCUMENT_ROOT'] = sys_get_temp_dir() . '/upd-old-' . uniqid();
        @mkdir($_SERVER['DOCUMENT_ROOT'], 0777, true);
        file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/version.php', '<?php const _CORE_VERSION = "2.0.0";');

        $svc = new class extends UpdateService {
            public function getRemoteVersion(): ?string { return '1.9.9'; }
        };

        $info = $svc->isUpdateAvailable();

        $this->assertFalse($info['update_available']);
        $this->assertSame('2.0.0', $info['current_version']);
        $this->assertSame('1.9.9', $info['remote_version']);
    }

    public function testPerformUpdateSuccessCleansTemporaryArtifacts(): void
    {
        $zipFile = tempnam(sys_get_temp_dir(), 'upd-zip-');
        file_put_contents($zipFile, 'zip-content');

        $rootExtract = sys_get_temp_dir() . '/upd-extract-' . uniqid();
        $extractedFolder = $rootExtract . '/balerocms-development';
        @mkdir($extractedFolder, 0777, true);
        file_put_contents($extractedFolder . '/marker.txt', 'ok');

        $svc = new class($zipFile, $extractedFolder) extends UpdateService {
            public function __construct(private string $zipFile, private string $extractedFolder) {}
            public function downloadUpdate(): array { return ['success' => true, 'zip_file' => $this->zipFile]; }
            public function extractUpdate(string $zipFile): array { return ['success' => true, 'extracted_folder' => $this->extractedFolder]; }
            public function installUpdate(string $extractedFolder): array { return ['success' => true, 'message' => 'installed']; }
        };

        $result = $svc->performUpdate();

        $this->assertTrue($result['success']);
        $this->assertSame('installed', $result['message']);
        $this->assertFileDoesNotExist($zipFile);
        $this->assertDirectoryDoesNotExist($extractedFolder);
    }

    public function testPerformUpdateReturnsInstallErrorWhenInstallFails(): void
    {
        $zipFile = tempnam(sys_get_temp_dir(), 'upd-zip-');
        file_put_contents($zipFile, 'zip-content');

        $rootExtract = sys_get_temp_dir() . '/upd-extract-' . uniqid();
        $extractedFolder = $rootExtract . '/balerocms-development';
        @mkdir($extractedFolder, 0777, true);

        $svc = new class($zipFile, $extractedFolder) extends UpdateService {
            public function __construct(private string $zipFile, private string $extractedFolder) {}
            public function downloadUpdate(): array { return ['success' => true, 'zip_file' => $this->zipFile]; }
            public function extractUpdate(string $zipFile): array { return ['success' => true, 'extracted_folder' => $this->extractedFolder]; }
            public function installUpdate(string $extractedFolder): array { return ['success' => false, 'message' => 'install failed']; }
        };

        $result = $svc->performUpdate();

        $this->assertFalse($result['success']);
        $this->assertSame('install failed', $result['message']);
        $this->assertFileDoesNotExist($zipFile);
        $this->assertDirectoryDoesNotExist($extractedFolder);
    }
}
