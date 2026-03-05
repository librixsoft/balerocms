<?php

declare(strict_types=1);

namespace Tests\App\Services;

use App\DTO\InstallerDTO;
use App\Mapper\InstallerMapper;
use App\Models\InstallerModel;
use App\Services\InstallerService;
use App\Views\InstallerViewModel;
use Framework\Core\ConfigSettings;
use Framework\Utils\Validator;
use PHPUnit\Framework\TestCase;

final class InstallerServiceTest extends TestCase
{
    public function testInstallerServiceFlows(): void
    {
        $model = $this->createMock(InstallerModel::class);
        $model->method('canConnectToDatabase')->willReturn(true);
        $model->expects($this->once())->method('install');
        $model->expects($this->once())->method('setInstalled');

        $viewModel = $this->createMock(InstallerViewModel::class);
        $viewModel->method('setInstallerParams')->willReturnCallback(fn(array $p = []) => $p + ['ok' => 1]);

        $validator = $this->createMock(Validator::class);
        $validator->method('fails')->willReturn(false);
        $validator->method('errors')->willReturn(['x' => 'y']);

        $mapper = $this->createMock(InstallerMapper::class);
        $mapper->expects($this->once())->method('mapAndSaveSettings');

        $config = $this->createMock(ConfigSettings::class);

        $s = new InstallerService();
        $r = new \ReflectionClass($s);
        foreach (['model'=>$model,'installerViewModel'=>$viewModel,'validator'=>$validator,'installerMapper'=>$mapper,'configSettings'=>$config] as $prop=>$val) {
            $p = $r->getProperty($prop);
            $p->setAccessible(true);
            $p->setValue($s, $val);
        }

        $this->assertTrue($s->canConnectToDatabase());
        $this->assertTrue($s->validateInstaller($this->createMock(InstallerDTO::class)));
        $this->assertSame(['x' => 'y'], $s->getValidationErrors());
        $this->assertArrayHasKey('db_ok', $s->prepareInstallerParams(['a' => 1]));

        $s->mapAndSaveSettings($this->createMock(InstallerDTO::class));
        $s->executeInstallation();
        $s->markAsInstalled();
        $this->assertSame(['ok' => 1], $s->prepareProgressBarParams());
    }
}
