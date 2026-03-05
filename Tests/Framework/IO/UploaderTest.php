<?php

declare(strict_types=1);

namespace Tests\Framework\IO;

use Framework\Config\SetupConfig;
use Framework\Core\ConfigSettings;
use Framework\IO\Uploader;
use PHPUnit\Framework\TestCase;

final class UploaderTest extends TestCase
{
    public function testUploadsPathGetterAndSetter(): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'cfg');
        file_put_contents($tmp, json_encode(['config'=>['database'=>['dbhost'=>'','dbuser'=>'','dbpass'=>'','dbname'=>''],'admin'=>['username'=>'u','passwd'=>'p','email'=>'','firstname'=>'','lastname'=>''],'system'=>['installed'=>true,'debug'=>true],'site'=>['language'=>'en','title'=>'','description'=>'','url'=>'','keywords'=>'','basepath'=>'/','theme'=>'default','footer'=>'','multilang'=>false,'editor'=>'']]], JSON_THROW_ON_ERROR));
        $cfg = new ConfigSettings(new SetupConfig($tmp));
        $cfg->getHandler();

        $u = new Uploader($cfg);
        $newPath = sys_get_temp_dir() . '/uploads-custom';
        $u->setUploadsPath($newPath);

        $this->assertSame($newPath, $u->getUploadsPath());
    }
}
