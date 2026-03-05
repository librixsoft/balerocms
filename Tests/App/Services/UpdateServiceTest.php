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
}
