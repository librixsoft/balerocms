<?php

declare(strict_types=1);

namespace Tests\Framework\Core;

use Framework\Config\SetupConfig;
use Framework\Core\ConfigSettings;
use Framework\Core\Model;
use Framework\Database\MySQL;
use PHPUnit\Framework\TestCase;

final class ModelTest extends TestCase
{
    public function testConstructorDoesNotConnectWhenInstalledIsNotYes(): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'cfg');
        file_put_contents($tmp, json_encode(['config'=>['database'=>['dbhost'=>'h','dbuser'=>'u','dbpass'=>'p','dbname'=>'d'],'admin'=>['username'=>'u','passwd'=>'p','email'=>'','firstname'=>'','lastname'=>''],'system'=>['installed'=>false,'debug'=>true],'site'=>['language'=>'en','title'=>'','description'=>'','url'=>'','keywords'=>'','basepath'=>'/','theme'=>'default','footer'=>'','multilang'=>false,'editor'=>'']]], JSON_THROW_ON_ERROR));
        $cfg = new ConfigSettings(new SetupConfig($tmp));
        $cfg->getHandler();

        $db = $this->createMock(MySQL::class);
        $db->expects($this->never())->method('connect');

        $m = new Model($cfg, $db);
        $this->assertSame($db, $m->getDb());

        $db2 = $this->createMock(MySQL::class);
        $m->setDb($db2);
        $this->assertSame($db2, $m->getDb());
    }

    public function testConstructorConnectsWhenInstalledIsYes(): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'cfg');
        file_put_contents($tmp, json_encode(['config'=>['database'=>['dbhost'=>'host','dbuser'=>'user','dbpass'=>'pass','dbname'=>'name'],'admin'=>['username'=>'u','passwd'=>'p','email'=>'','firstname'=>'','lastname'=>''],'system'=>['installed'=>'yes','debug'=>true],'site'=>['language'=>'en','title'=>'','description'=>'','url'=>'','keywords'=>'','basepath'=>'/','theme'=>'default','footer'=>'','multilang'=>false,'editor'=>'']]], JSON_THROW_ON_ERROR));
        $cfg = new ConfigSettings(new SetupConfig($tmp));
        $cfg->getHandler();

        $db = $this->createMock(MySQL::class);
        $db->expects($this->once())
            ->method('connect')
            ->with('host', 'user', 'pass', 'name');

        $m = new Model($cfg, $db);
        $this->assertSame($db, $m->getDb());
    }
}
