<?php

declare(strict_types=1);

namespace Tests\Framework\IO;

use Framework\Config\SetupConfig;
use Framework\Core\ConfigSettings;
use Framework\IO\Uploader;
use Framework\Exceptions\UploaderException;
use PHPUnit\Framework\TestCase;

final class UploaderTest extends TestCase
{
    private string $testDir;

    protected function setUp(): void
    {
        $this->testDir = sys_get_temp_dir() . '/uploader_sonar_test_' . uniqid();
        @mkdir($this->testDir, 0777, true);
    }

    protected function tearDown(): void
    {
        $this->recursiveRmdir($this->testDir);
    }

    private function recursiveRmdir($dir) {
        if (!is_dir($dir)) return;
        foreach (array_diff(scandir($dir), ['.', '..']) as $f) {
            (is_dir("$dir/$f")) ? $this->recursiveRmdir("$dir/$f") : unlink("$dir/$f");
        }
        rmdir($dir);
    }

    private function makeUploader(): Uploader
    {
        $tmp = tempnam(sys_get_temp_dir(), 'cfg');
        file_put_contents($tmp, json_encode(['config'=>['site'=>['basepath'=>'/']]]));
        $cfg = new ConfigSettings(new SetupConfig($tmp));
        return new Uploader($cfg, $this->testDir);
    }

    public function testImageUploadLifecycle(): void
    {
        $u = $this->makeUploader();
        $dummyImg = $this->testDir . '/pixel.gif';
        file_put_contents($dummyImg, base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7'));

        // Probar subida sin el parámetro $meta
        $url = $u->image(['name'=>'pixel.gif','tmp_name'=>$dummyImg,'error'=>UPLOAD_ERR_OK]);
        $hash = md5_file($dummyImg);

        $this->assertStringContainsString($hash, $url);

        $u->addRecordToMetadata($hash, ['id'=>1, 'type'=>'post']);
        $list = $u->getAllMediaMetadata();
        $this->assertEquals('post #1', $list[0]['records_summary']);

        $u->removeRecordFromAllMetadata(1, 'post');
        $u->deleteMedia($hash);
        $this->assertFileDoesNotExist($this->testDir.'/'.$hash.'.json');
    }

    public function testUploadFailsOnInvalidExtension(): void
    {
        $u = $this->makeUploader();
        $this->expectException(UploaderException::class);
        $u->image(['name'=>'test.exe','tmp_name'=>$this->testDir.'/none','error'=>UPLOAD_ERR_OK]);
    }
}

