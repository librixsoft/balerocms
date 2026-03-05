<?php

declare(strict_types=1);

namespace Tests\Framework\IO;

use Framework\Config\SetupConfig;
use Framework\Core\ConfigSettings;
use Framework\IO\Uploader;
use PHPUnit\Framework\TestCase;

final class UploaderMetadataTest extends TestCase
{
    private function makeUploader(string $dir): Uploader
    {
        $tmp = tempnam(sys_get_temp_dir(), 'cfg');
        file_put_contents($tmp, json_encode(['config'=>['database'=>['dbhost'=>'','dbuser'=>'','dbpass'=>'','dbname'=>''],'admin'=>['username'=>'u','passwd'=>'p','email'=>'','firstname'=>'','lastname'=>''],'system'=>['installed'=>true,'debug'=>true],'site'=>['language'=>'en','title'=>'','description'=>'','url'=>'','keywords'=>'','basepath'=>'/','theme'=>'default','footer'=>'','multilang'=>false,'editor'=>'']]], JSON_THROW_ON_ERROR));
        $cfg = new ConfigSettings(new SetupConfig($tmp));
        $cfg->getHandler();
        $u = new Uploader($cfg);
        $u->setUploadsPath($dir);
        return $u;
    }

    public function testAddRemoveListAndDeleteMedia(): void
    {
        $dir = sys_get_temp_dir() . '/uploads-' . uniqid();
        @mkdir($dir, 0777, true);
        $hash = 'abc123';
        file_put_contents($dir.'/'.$hash.'.jpg', 'img');
        file_put_contents($dir.'/'.$hash.'.json', json_encode([
            'filename'=>$hash.'.jpg','extension'=>'jpg','size_bytes'=>2048,'uploaded_at'=>'2026-03-05T00:00:00+00:00','records'=>[]
        ]));

        $u = $this->makeUploader($dir);
        $u->addRecordToMetadata($hash, ['id'=>10,'type'=>'page','url'=>'/x']);
        $u->addRecordToMetadata($hash, ['id'=>10,'type'=>'page','url'=>'/x']);

        $data = json_decode(file_get_contents($dir.'/'.$hash.'.json'), true);
        $this->assertCount(1, $data['records']);

        $list = $u->getAllMediaMetadata();
        $this->assertSame('2 KB', $list[0]['size_formatted']);
        $this->assertStringContainsString('page #10', $list[0]['records_summary']);

        $u->removeRecordFromAllMetadata(10, 'page');
        $data2 = json_decode(file_get_contents($dir.'/'.$hash.'.json'), true);
        $this->assertSame([], $data2['records']);

        $u->deleteMedia($hash);
        $this->assertFileDoesNotExist($dir.'/'.$hash.'.json');
        $this->assertFileDoesNotExist($dir.'/'.$hash.'.jpg');

        @rmdir($dir);
    }

    public function testDeleteMediaThrowsWhenInUseOrMetadataMissing(): void
    {
        $dir = sys_get_temp_dir() . '/uploads-exc-' . uniqid();
        @mkdir($dir, 0777, true);
        $hash = 'inuse';
        file_put_contents($dir.'/'.$hash.'.jpg', 'img');
        file_put_contents($dir.'/'.$hash.'.json', json_encode([
            'filename'=>$hash.'.jpg','extension'=>'jpg','size_bytes'=>1024,'uploaded_at'=>'2026-03-05T00:00:00+00:00','records'=>[['id'=>1,'type'=>'page']]
        ]));

        $u = $this->makeUploader($dir);

        $this->expectException(\Framework\Exceptions\UploaderException::class);
        $u->deleteMedia($hash);
    }

    public function testGetAllMediaMetadataSortsByNewestAndFormatsMb(): void
    {
        $dir = sys_get_temp_dir() . '/uploads-sort-' . uniqid();
        @mkdir($dir, 0777, true);

        file_put_contents($dir.'/old.json', json_encode([
            'filename'=>'old.jpg','extension'=>'jpg','size_bytes'=>2048,'uploaded_at'=>'2026-03-01T00:00:00+00:00','records'=>[]
        ]));
        file_put_contents($dir.'/new.json', json_encode([
            'filename'=>'new.jpg','extension'=>'jpg','size_bytes'=>2*1024*1024,'uploaded_at'=>'2026-03-06T00:00:00+00:00','records'=>[]
        ]));

        $u = $this->makeUploader($dir);
        $all = $u->getAllMediaMetadata();

        $this->assertSame('new.jpg', $all[0]['filename']);
        $this->assertSame('2 MB', $all[0]['size_formatted']);
        $this->assertSame('Not linked', $all[0]['records_summary']);

        @unlink($dir.'/old.json');
        @unlink($dir.'/new.json');
        @rmdir($dir);
    }
}
