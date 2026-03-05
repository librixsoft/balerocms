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
    private function makeService(
        ?AdminModel $model = null,
        ?AdminViewModel $vm = null,
        ?UpdateService $update = null,
        ?UploaderService $uploader = null,
        ?ConfigSettings $configSettings = null,
        ?\Framework\Utils\Validator $validator = null,
        ?\App\Mapper\AdminSettingsMapper $mapper = null,
    ): AdminService {
        $svc = new AdminService();
        $r = new \ReflectionClass($svc);

        $deps = [
            'model' => $model ?? $this->createMock(AdminModel::class),
            'viewModel' => $vm ?? $this->createMock(AdminViewModel::class),
            'updateService' => $update ?? $this->createMock(UpdateService::class),
            'uploaderService' => $uploader ?? $this->createMock(UploaderService::class),
            'configSettings' => $configSettings ?? $this->createMock(ConfigSettings::class),
            'validator' => $validator ?? $this->createMock(\Framework\Utils\Validator::class),
            'adminSettingsMapper' => $mapper ?? $this->createMock(\App\Mapper\AdminSettingsMapper::class),
        ];

        foreach ($deps as $prop => $val) {
            $p = $r->getProperty($prop);
            $p->setAccessible(true);
            $p->setValue($svc, $val);
        }

        return $svc;
    }

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

        $svc = $this->makeService($model, $vm, $update, $uploader);

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

    public function testCrudDelegatesToModelAndUpdateService(): void
    {
        $model = $this->createMock(AdminModel::class);
        $model->expects($this->once())->method('createPage')->with(['title' => 'x'])->willReturn(11);
        $model->expects($this->once())->method('updatePage')->with(11, ['title' => 'y']);
        $model->expects($this->once())->method('deletePage')->with(11);
        $model->expects($this->once())->method('createBlock')->with(['title' => 'b'])->willReturn(22);
        $model->expects($this->once())->method('updateBlock')->with(22, ['title' => 'c']);
        $model->expects($this->once())->method('deleteBlock')->with(22);

        $update = $this->createMock(UpdateService::class);
        $update->expects($this->once())->method('selfUpdate')->willReturn(['success' => true]);
        $update->expects($this->once())->method('performUpdate')->willReturn(['success' => true]);

        $svc = $this->makeService($model, null, $update);

        $this->assertSame(11, $svc->createPage(['title' => 'x']));
        $svc->updatePage(11, ['title' => 'y']);
        $svc->deletePage(11);
        $this->assertSame(22, $svc->createBlock(['title' => 'b']));
        $svc->updateBlock(22, ['title' => 'c']);
        $svc->deleteBlock(22);
        $this->assertTrue($svc->selfUpdateService()['success']);
        $this->assertTrue($svc->performUpdate()['success']);
    }

    public function testValidationAndMappingDelegation(): void
    {
        $validator = $this->createMock(\Framework\Utils\Validator::class);
        $validator->expects($this->once())->method('validate');
        $validator->method('fails')->willReturn(false);
        $validator->expects($this->once())->method('errors')->willReturn(['a' => 'b']);

        $mapper = $this->createMock(\App\Mapper\AdminSettingsMapper::class);
        $mapper->expects($this->once())->method('mapAndSaveSettings');

        $settings = $this->createMock(\App\DTO\SettingsDTO::class);

        $svc = $this->makeService(null, null, null, null, $this->createMock(ConfigSettings::class), $validator, $mapper);

        $this->assertTrue($svc->validateSettings($settings));
        $svc->mapAndSaveSettings($settings);
        $this->assertSame(['a' => 'b'], $svc->getValidationErrors());
    }
}
