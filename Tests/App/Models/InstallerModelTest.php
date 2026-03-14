<?php

declare(strict_types=1);

namespace Tests\App\Models;

use Framework\Core\ConfigSettings;
use Framework\Core\Model;
use Framework\Database\MySQL;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/InstallerModelStubs.php';

final class FakeMySQL extends MySQL
{
    public string $lastQuery = '';

    public function connect(string $host, string $user, string $pass, ?string $dbname = null): void
    {
        // no-op
    }

    public function isStatus(): bool
    {
        return true;
    }

    public function query(string $query, array $params = []): void
    {
        $this->lastQuery = $query;
    }
}

final class InstallerModelTest extends TestCase
{
    private function injectDependencies(\App\Models\InstallerModel $model, Model $core, ConfigSettings $cfg): void
    {
        $r = new \ReflectionClass(\App\Models\InstallerModel::class);
        foreach (['model' => $core, 'configSettings' => $cfg] as $k => $v) {
            $p = $r->getProperty($k);
            $p->setAccessible(true);
            $p->setValue($model, $v);
        }
    }

    public function testCanConnectToDatabaseFalseOnException(): void
    {
        $db = $this->createMock(MySQL::class);
        $db->method('connect')->willThrowException(new \RuntimeException('x'));

        $core = $this->createMock(Model::class);
        $core->method('getDb')->willReturn($db);

        $cfg = $this->createMock(ConfigSettings::class);
        $cfg->dbhost = 'h'; $cfg->dbuser = 'u'; $cfg->dbpass = 'p'; $cfg->dbname = 'd';

        $m = new \App\Models\InstallerModel();
        $this->injectDependencies($m, $core, $cfg);

        $this->assertFalse($m->canConnectToDatabase());
    }

    public function testCanConnectToDatabaseReturnsTrueWhenConnectionAndCreateSucceed(): void
    {
        $db = new FakeMySQL();

        $tmp = tempnam(sys_get_temp_dir(), 'cfg');
        file_put_contents($tmp, json_encode(['config'=>['database'=>['dbhost'=>'h','dbuser'=>'u','dbpass'=>'p','dbname'=>'demo'],'admin'=>['username'=>'u','passwd'=>'p','email'=>'','firstname'=>'','lastname'=>''],'system'=>['installed'=>'no','debug'=>true],'site'=>['language'=>'en','title'=>'','description'=>'','url'=>'','keywords'=>'','basepath'=>'/','theme'=>'default','footer'=>'','multilang'=>false,'editor'=>'']]], JSON_THROW_ON_ERROR));
        $cfg = new ConfigSettings(new \Framework\Config\SetupConfig($tmp));
        $cfg->getHandler();

        $core = new Model($cfg, $db);

        $m = new \App\Models\InstallerModel();
        $this->injectDependencies($m, $core, $cfg);

        $this->assertTrue($m->canConnectToDatabase());
        $this->assertSame('CREATE DATABASE IF NOT EXISTS `demo`;', $db->lastQuery);
    }

    public function testInstallThrowsWhenCannotConnect(): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'cfg');
        file_put_contents($tmp, json_encode(['config'=>['database'=>['dbhost'=>'h','dbuser'=>'u','dbpass'=>'p','dbname'=>'d'],'admin'=>['username'=>'u','passwd'=>'p','email'=>'','firstname'=>'','lastname'=>''],'system'=>['installed'=>'no','debug'=>true],'site'=>['language'=>'en','title'=>'','description'=>'','url'=>'','keywords'=>'','basepath'=>'/','theme'=>'default','footer'=>'','multilang'=>false,'editor'=>'']]], JSON_THROW_ON_ERROR));
        $cfg = new ConfigSettings(new \Framework\Config\SetupConfig($tmp));
        $cfg->getHandler();

        $core = $this->createMock(Model::class);

        $m = new class() extends \App\Models\InstallerModel {
            public function canConnectToDatabase(): bool
            {
                return false;
            }
        };

        $this->injectDependencies($m, $core, $cfg);

        try {
            $m->install();
            $this->fail('Expected ModelException');
        } catch (\Framework\Exceptions\ModelException $e) {
            $this->assertStringContainsString('Installation failed: Unable to connect to or create the database.', $e->getMessage());
            $this->assertSame('no', $cfg->installed);
        }
    }

    public function testInstallThrowsWhenSqlFileMissing(): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'cfg');
        file_put_contents($tmp, json_encode(['config'=>['database'=>['dbhost'=>'h','dbuser'=>'u','dbpass'=>'p','dbname'=>'demo'],'admin'=>['username'=>'u','passwd'=>'p','email'=>'','firstname'=>'','lastname'=>''],'system'=>['installed'=>'yes','debug'=>true],'site'=>['language'=>'en','title'=>'','description'=>'','url'=>'','keywords'=>'','basepath'=>'/','theme'=>'default','footer'=>'','multilang'=>false,'editor'=>'']]], JSON_THROW_ON_ERROR));
        $cfg = new ConfigSettings(new \Framework\Config\SetupConfig($tmp));
        $cfg->getHandler();

        $db = $this->createMock(MySQL::class);
        $core = $this->createMock(Model::class);
        $core->method('getDb')->willReturn($db);

        $m = new class() extends \App\Models\InstallerModel {
            public function canConnectToDatabase(): bool
            {
                return true;
            }
        };

        $this->injectDependencies($m, $core, $cfg);
        $m->setTablesSqlPath('/tmp/does-not-exist.sql');

        try {
            $m->install();
            $this->fail('Expected ModelException');
        } catch (\Framework\Exceptions\ModelException $e) {
            $this->assertStringContainsString('SQL file not found:', $e->getMessage());
            $this->assertSame('no', $cfg->installed);
        }
    }

    public function testInstallThrowsWhenSqlFileUnreadable(): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'cfg');
        file_put_contents($tmp, json_encode(['config'=>['database'=>['dbhost'=>'h','dbuser'=>'u','dbpass'=>'p','dbname'=>'demo'],'admin'=>['username'=>'u','passwd'=>'p','email'=>'','firstname'=>'','lastname'=>''],'system'=>['installed'=>'yes','debug'=>true],'site'=>['language'=>'en','title'=>'','description'=>'','url'=>'','keywords'=>'','basepath'=>'/','theme'=>'default','footer'=>'','multilang'=>false,'editor'=>'']]], JSON_THROW_ON_ERROR));
        $cfg = new ConfigSettings(new \Framework\Config\SetupConfig($tmp));
        $cfg->getHandler();

        $db = $this->createMock(MySQL::class);
        $core = $this->createMock(Model::class);
        $core->method('getDb')->willReturn($db);

        $m = new class() extends \App\Models\InstallerModel {
            public function canConnectToDatabase(): bool
            {
                return true;
            }
        };

        $this->injectDependencies($m, $core, $cfg);
        $m->setTablesSqlPath('force-unreadable');

        try {
            $m->install();
            $this->fail('Expected ModelException');
        } catch (\Framework\Exceptions\ModelException $e) {
            $this->assertStringContainsString('Unable to read SQL file:', $e->getMessage());
            $this->assertSame('no', $cfg->installed);
        }
    }

    public function testInstallLoadsSqlAndCreatesTables(): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'cfg');
        file_put_contents($tmp, json_encode(['config'=>['database'=>['dbhost'=>'h','dbuser'=>'u','dbpass'=>'p','dbname'=>'demo'],'admin'=>['username'=>'u','passwd'=>'p','email'=>'','firstname'=>'','lastname'=>''],'system'=>['installed'=>'no','debug'=>true],'site'=>['language'=>'en','title'=>'','description'=>'','url'=>'','keywords'=>'','basepath'=>'/','theme'=>'default','footer'=>'','multilang'=>false,'editor'=>'']]], JSON_THROW_ON_ERROR));
        $cfg = new ConfigSettings(new \Framework\Config\SetupConfig($tmp));
        $cfg->getHandler();

        $sqlPath = tempnam(sys_get_temp_dir(), 'sql');
        file_put_contents($sqlPath, 'CREATE DATABASE {dbname};');

        $db = $this->createMock(MySQL::class);
        $db->expects($this->once())->method('connect')->with('h', 'u', 'p', 'demo');
        $db->expects($this->once())->method('create')->with('CREATE DATABASE demo;');

        $core = $this->createMock(Model::class);
        $core->method('getDb')->willReturn($db);

        $m = new class() extends \App\Models\InstallerModel {
            public function canConnectToDatabase(): bool
            {
                return true;
            }
        };

        $this->injectDependencies($m, $core, $cfg);
        $m->setTablesSqlPath($sqlPath);

        $m->install();
    }

    public function testSetInstalledAndTablesPathAccessors(): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'cfg');
        file_put_contents($tmp, json_encode(['config'=>['database'=>['dbhost'=>'h','dbuser'=>'u','dbpass'=>'p','dbname'=>'d'],'admin'=>['username'=>'u','passwd'=>'p','email'=>'','firstname'=>'','lastname'=>''],'system'=>['installed'=>'no','debug'=>true],'site'=>['language'=>'en','title'=>'','description'=>'','url'=>'','keywords'=>'','basepath'=>'/','theme'=>'default','footer'=>'','multilang'=>false,'editor'=>'']]], JSON_THROW_ON_ERROR));
        $cfg = new ConfigSettings(new \Framework\Config\SetupConfig($tmp));
        $cfg->getHandler();

        $m = new \App\Models\InstallerModel();
        $core = $this->createMock(Model::class);
        $this->injectDependencies($m, $core, $cfg);

        $this->assertIsString($m->getTablesSqlPath());
        $m->setTablesSqlPath('/tmp/tables.sql');
        $this->assertSame('/tmp/tables.sql', $m->getTablesSqlPath());

        $m->setInstalled();
        $this->assertSame('yes', $cfg->installed);
    }
}
