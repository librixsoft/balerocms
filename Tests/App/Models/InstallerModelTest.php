<?php

declare(strict_types=1);

namespace Tests\App\Models;

use App\Models\InstallerModel;
use Framework\Core\ConfigSettings;
use Framework\Core\Model;
use Framework\Database\MySQL;
use PHPUnit\Framework\TestCase;

final class InstallerModelTest extends TestCase
{
    public function testCanConnectToDatabaseFalseOnException(): void
    {
        $db = $this->createMock(MySQL::class);
        $db->method('connect')->willThrowException(new \RuntimeException('x'));

        $core = $this->createMock(Model::class);
        $core->method('getDb')->willReturn($db);

        $cfg = $this->createMock(ConfigSettings::class);
        $cfg->dbhost = 'h'; $cfg->dbuser = 'u'; $cfg->dbpass = 'p'; $cfg->dbname = 'd';

        $m = new InstallerModel();
        $r = new \ReflectionClass($m);
        foreach (['model'=>$core,'configSettings'=>$cfg] as $k=>$v){$p=$r->getProperty($k);$p->setAccessible(true);$p->setValue($m,$v);}        

        $this->assertFalse($m->canConnectToDatabase());
    }

    public function testSetInstalledAndTablesPathAccessors(): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'cfg');
        file_put_contents($tmp, json_encode(['config'=>['database'=>['dbhost'=>'h','dbuser'=>'u','dbpass'=>'p','dbname'=>'d'],'admin'=>['username'=>'u','passwd'=>'p','email'=>'','firstname'=>'','lastname'=>''],'system'=>['installed'=>'no','debug'=>true],'site'=>['language'=>'en','title'=>'','description'=>'','url'=>'','keywords'=>'','basepath'=>'/','theme'=>'default','footer'=>'','multilang'=>false,'editor'=>'']]], JSON_THROW_ON_ERROR));
        $cfg = new ConfigSettings(new \Framework\Config\SetupConfig($tmp));
        $cfg->getHandler();

        $m = new InstallerModel();
        $r = new \ReflectionClass($m);
        $p=$r->getProperty('configSettings');$p->setAccessible(true);$p->setValue($m,$cfg);

        $this->assertIsString($m->getTablesSqlPath());
        $m->setTablesSqlPath('/tmp/tables.sql');
        $this->assertSame('/tmp/tables.sql', $m->getTablesSqlPath());

        $m->setInstalled();
        $this->assertSame('yes', $cfg->installed);
    }
}
