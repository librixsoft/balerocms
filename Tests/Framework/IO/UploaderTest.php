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

    public function testDefaultUploadPath(): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'cfg');
        file_put_contents($tmp, json_encode([]));
        $cfg = new ConfigSettings(new SetupConfig($tmp));
        $u = new Uploader($cfg);
        $this->assertStringContainsString('assets/images/uploads', $u->getUploadsPath());
    }

    public function testSetAndGetUploadsPath(): void
    {
        $u = $this->makeUploader();
        $u->setUploadsPath('/test/path');
        $this->assertEquals('/test/path', $u->getUploadsPath());
    }

    public function testImageUploadLifecycle(): void
    {
        $u = $this->makeUploader();
        $dummyImg = $this->testDir . '/pixel.gif';
        file_put_contents($dummyImg, base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7'));

        // Probar subida sin el parámetro $meta
        $metadata = $u->image(['name'=>'pixel.gif','tmp_name'=>$dummyImg,'error'=>UPLOAD_ERR_OK]);
        $url = $metadata['url'];
        $hash = md5_file($dummyImg);

        $this->assertStringContainsString($hash, $url);

        $u->addRecordToMetadata($hash, ['id'=>1, 'type'=>'post']);
        $list = $u->getAllMediaMetadata();
        $this->assertEquals('post #1', $list[0]['records_summary']);

        $u->removeRecordFromAllMetadata(1, 'post');
        $u->deleteMedia($hash);
        $this->assertFileDoesNotExist($this->testDir.'/'.$hash.'.json');
    }

    public function testImageUploadFailsOnError(): void
    {
        $u = $this->makeUploader();
        $this->expectException(UploaderException::class);
        $u->image(['name' => 'test.png', 'tmp_name' => '', 'error' => UPLOAD_ERR_INI_SIZE]);
    }

    public function testImageUploadFailsOnInvalidImage(): void
    {
        $u = $this->makeUploader();
        $dummyFile = $this->testDir . '/not-image.txt';
        file_put_contents($dummyFile, 'not an image');
        $this->expectException(UploaderException::class);
        $u->image(['name' => 'test.png', 'tmp_name' => $dummyFile, 'error' => UPLOAD_ERR_OK]);
    }

    public function testImageUploadFailsOnInvalidExtension(): void
    {
        $u = $this->makeUploader();
        $dummyImg = $this->testDir . '/pixel.exe';
        file_put_contents($dummyImg, base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7'));
        $this->expectException(UploaderException::class);
        $u->image(['name' => 'pixel.exe', 'tmp_name' => $dummyImg, 'error' => UPLOAD_ERR_OK]);
    }

    public function testImageUploadFailsOnMkdir(): void
    {
        // To force mkdir to fail even as root, make the parent part of the path a regular file
        $fileAsParent = $this->testDir . '/file_not_dir';
        file_put_contents($fileAsParent, 'not a directory');
        
        $root = $fileAsParent . '/subfolder';
        $u = $this->makeUploader();
        $u->setUploadsPath($root);

        $dummyImg = $this->testDir . '/pixel.gif';
        file_put_contents($dummyImg, base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7'));

        $this->expectException(UploaderException::class);
        $this->expectExceptionMessage("Failed to create upload directory.");
        $u->image(['name' => 'pixel.gif', 'tmp_name' => $dummyImg, 'error' => UPLOAD_ERR_OK]);
    }

    public function testImageFailsOnMove(): void
    {
        $u = new class($this->makeUploader()->getUploadsPath()) extends Uploader {
            public function __construct(string $path) {
                $tmp = tempnam(sys_get_temp_dir(), 'cfg');
                file_put_contents($tmp, json_encode(['config'=>['site'=>['basepath'=>'/']]]));
                $cfg = new ConfigSettings(new SetupConfig($tmp));
                parent::__construct($cfg, $path);
            }
            protected function moveFile(string $from, string $to): bool {
                return false;
            }
        };

        $dummyImg = $this->testDir . '/pixel.gif';
        file_put_contents($dummyImg, base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7'));

        $this->expectException(UploaderException::class);
        $u->image(['name' => 'pixel.gif', 'tmp_name' => $dummyImg, 'error' => UPLOAD_ERR_OK]);
    }

    public function testImageSkipsMetadataCreationIfExists(): void
    {
        $u = $this->makeUploader();
        $dummyImg = $this->testDir . '/pixel.gif';
        file_put_contents($dummyImg, base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7'));
        $hash = md5_file($dummyImg);

        @mkdir($this->testDir, 0777, true);
        file_put_contents($this->testDir . '/' . $hash . '.json', '{"existing": "data"}');

        $metadata = $u->image(['name' => 'pixel.gif', 'tmp_name' => $dummyImg, 'error' => UPLOAD_ERR_OK]);
        $url = $metadata['url'];
        $this->assertStringContainsString($hash, $url);

        $data = json_decode(file_get_contents($this->testDir . '/' . $hash . '.json'), true);
        $this->assertEquals("data", $data['existing'] ?? null);
    }

    public function testGetAllMediaMetadataEmpty(): void
    {
        $u = $this->makeUploader();
        $u->setUploadsPath($this->testDir . '/does_not_exist_folder');
        $this->assertSame([], $u->getAllMediaMetadata());
    }
    
    public function testGetAllMediaMetadataSizeFormatted(): void
    {
        $u = $this->makeUploader();
        
        $hash1 = md5("1");
        $hash2 = md5("2");
        file_put_contents($this->testDir . '/' . $hash1 . '.json', json_encode([
            'size_bytes' => 2097152,
            'uploaded_at' => '2025-01-01T00:00:00Z',
            'records' => []
        ]));
        file_put_contents($this->testDir . '/' . $hash2 . '.json', json_encode([
            'size_bytes' => 512,
            'uploaded_at' => '2025-01-02T00:00:00Z',
            'records' => [['id'=>2, 'type'=>'page']]
        ]));

        $list = $u->getAllMediaMetadata();
        $this->assertCount(2, $list);
        
        $this->assertEquals('2 MB', $list[1]['size_formatted']);
        $this->assertEquals('0.5 KB', $list[0]['size_formatted']);
        $this->assertEquals('Not linked', $list[1]['records_summary']);
        $this->assertEquals('page #2', $list[0]['records_summary']);
    }

    public function testAddRecordToMetadataNotExists(): void
    {
        $u = $this->makeUploader();
        $u->addRecordToMetadata('not-a-hash', ['id'=>1, 'type'=>'post']);
        $this->assertTrue(true);
    }
    
    public function testAddRecordToMetadataDuplicates(): void
    {
        $u = $this->makeUploader();
        $dummyImg = $this->testDir . '/pixel.gif';
        file_put_contents($dummyImg, base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7'));
        $u->image(['name'=>'pixel.gif','tmp_name'=>$dummyImg,'error'=>UPLOAD_ERR_OK]);
        $hash = md5_file($dummyImg);

        $u->addRecordToMetadata($hash, ['id'=>1, 'type'=>'post']);
        $u->addRecordToMetadata($hash, ['id'=>1, 'type'=>'post']);

        $list = $u->getAllMediaMetadata();
        $this->assertCount(1, $list[0]['records']);
    }
    
    public function testDeleteMediaThrowsIfNotFound(): void
    {
        $u = $this->makeUploader();
        $this->expectException(UploaderException::class);
        $u->deleteMedia('not-a-hash');
    }

    public function testDeleteMediaThrowsIfInUse(): void
    {
        $u = $this->makeUploader();
        $dummyImg = $this->testDir . '/pixel.gif';
        file_put_contents($dummyImg, base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7'));
        $u->image(['name'=>'pixel.gif','tmp_name'=>$dummyImg,'error'=>UPLOAD_ERR_OK]);
        $hash = md5_file($dummyImg);

        $u->addRecordToMetadata($hash, ['id'=>1, 'type'=>'post']);
        $this->expectException(UploaderException::class);
        $this->expectExceptionMessage('Cannot delete media. It is in use (JSON).');
        $u->deleteMedia($hash);
    }
}

