<?php

declare(strict_types=1);

namespace Tests\App\Views;

use App\Views\InstallerViewModel;
use Framework\Core\ConfigSettings;
use Framework\Core\ViewModel;
use PHPUnit\Framework\TestCase;

final class InstallerViewModelTest extends TestCase
{
    public function testSetInstallerParamsBuildsExpectedFlagsAndValues(): void
    {
        $_SESSION['lang'] = 'es';

        $tmp = tempnam(sys_get_temp_dir(), 'cfg');
        file_put_contents($tmp, json_encode(['config'=>['database'=>['dbhost'=>'localhost','dbuser'=>'root','dbpass'=>'','dbname'=>'db'],'admin'=>['username'=>'admin','passwd'=>'x','email'=>'a@b.com','firstname'=>'A','lastname'=>'B'],'system'=>['installed'=>true,'debug'=>true],'site'=>['language'=>'en','title'=>'','description'=>'','url'=>'http://localhost','keywords'=>'','basepath'=>'/','theme'=>'default','footer'=>'','multilang'=>false,'editor'=>'']]], JSON_THROW_ON_ERROR));
        $config = new ConfigSettings(new \Framework\Config\SetupConfig($tmp));
        $config->getHandler();

        $vm = new InstallerViewModel();

        $r = new \ReflectionClass($vm);
        foreach (['config' => $config, 'viewModel' => new ViewModel()] as $prop => $val) {
            $p = $r->getProperty($prop);
            $p->setAccessible(true);
            $p->setValue($vm, $val);
        }

        $out = $vm->setInstallerParams(['db_ok' => true]);

        $this->assertSame('selected', $out['lang_selected_es']);
        $this->assertSame('', $out['lang_selected_en']);
        $this->assertTrue($out['db_ok']);
        $this->assertTrue($out['fields_valid']);
    }
}
