<?php

declare(strict_types=1);

namespace Tests\App\Services;

use App\Models\AdminModel;
use App\Services\AdminService;
use App\Services\UpdateService;
use App\Services\UploaderService;
use App\Views\AdminViewModel;
use Framework\Core\ConfigSettings;
use PHPUnit\Framework\TestCase;

final class AdminServiceTest extends TestCase
{
    public function testBasicViewParamsAndSortHelpers(): void
    {
        $model = $this->createMock(AdminModel::class);
        $model->method('getVirtualPages')->willReturn([['sort_order' => 2], ['sort_order' => 5]]);
        $model->method('getBlocks')->willReturn([['sort_order' => 1], ['sort_order' => 7]]);
        $model->method('getPagesCount')->willReturn(10);
        $model->method('getBlocksCount')->willReturn(20);
        $model->method('getPageById')->willReturn(['id' => 1]);
        $model->method('getBlockById')->willReturn(['id' => 2]);

        $vm = $this->createMock(AdminViewModel::class);
        $vm->method('getSettingsParams')->willReturnArgument(0);
        $vm->method('getPagesParams')->willReturnArgument(0);
        $vm->method('getAllPagesParams')->willReturnArgument(0);
        $vm->method('getEditPageParams')->willReturnArgument(0);
        $vm->method('getAllBlocksParams')->willReturnArgument(0);
        $vm->method('getNewBlockParams')->willReturnArgument(0);
        $vm->method('getEditBlockParams')->willReturnArgument(0);
        $vm->method('getUpdateParams')->willReturnArgument(0);
        $vm->method('getMediaParams')->willReturnArgument(0);
        $vm->method('getThemesParams')->willReturnArgument(0);

        $update = $this->createMock(UpdateService::class);
        $update->method('isUpdateAvailable')->willReturn(['update_available' => false]);

        $uploader = $this->createMock(UploaderService::class);
        $uploader->method('getAllMedia')->willReturn([['hash' => 'x']]);

        $svc = new AdminService();
        $r = new \ReflectionClass($svc);
        foreach ([
            'model'=>$model,'viewModel'=>$vm,'updateService'=>$update,'uploaderService'=>$uploader,
            'configSettings'=>$this->createMock(ConfigSettings::class),
            'validator'=>$this->createMock(\Framework\Utils\Validator::class),
            'adminSettingsMapper'=>$this->createMock(\App\Mapper\AdminSettingsMapper::class),
        ] as $prop=>$val){$p=$r->getProperty($prop);$p->setAccessible(true);$p->setValue($svc,$val);}        

        $this->assertSame(6, $svc->getNextPageSortOrder());
        $this->assertSame(8, $svc->getNextBlockSortOrder());
        $this->assertArrayHasKey('media_count', $svc->getSettingsViewParams());
        $this->assertArrayHasKey('next_sort_order', $svc->getNewPageViewParams());
        $this->assertArrayHasKey('pages', $svc->getAllPagesViewParams());
        $this->assertArrayHasKey('page', $svc->getEditPageViewParams(1));
        $this->assertArrayHasKey('blocks', $svc->getAllBlocksViewParams());
        $this->assertArrayHasKey('next_sort_order', $svc->getNewBlockViewParams());
        $this->assertArrayHasKey('block', $svc->getEditBlockViewParams(2));
        $this->assertArrayHasKey('update_available', $svc->getUpdateViewParams());
        $this->assertArrayHasKey('media_items', $svc->getMediaViewParams());
        $this->assertArrayHasKey('media_count', $svc->getThemesViewParams());
    }
}
