<?php

declare(strict_types=1);

namespace Tests\App\Services;

use App\Models\Admin\AdminPagesModel;
use App\Models\Admin\AdminBlocksModel;
use App\Services\Admin\AdminContentService;
use App\Services\Admin\AdminPagesService;
use App\Services\Admin\AdminBlocksService;
use App\Services\Admin\AdminMediaService;
use App\Models\Admin\AdminMediaModel;
use App\Views\AdminViewModel;
use PHPUnit\Framework\TestCase;

final class AdminContentServiceTest extends TestCase
{
    private function makeService(
        ?AdminPagesModel $pagesModel = null,
        ?AdminBlocksModel $blocksModel = null,
        ?AdminMediaModel $mediaModel = null,
        ?AdminViewModel $vm = null
    ): AdminContentService {
        $svc = new AdminContentService();

        $pagesSvc = new AdminPagesService();
        $blocksSvc = new AdminBlocksService();
        $mediaSvc = $this->createMock(AdminMediaService::class);

        $vm = $vm ?? $this->createMock(AdminViewModel::class);
        $dbModel = $this->createMock(\Framework\Core\Model::class);
        $utils = $this->createMock(\Framework\Utils\Utils::class);
        $pagesModel = $pagesModel ?? new AdminPagesModel($dbModel, $utils);
        $blocksModel = $blocksModel ?? new AdminBlocksModel($dbModel);
        $mediaModel = $mediaModel ?? new AdminMediaModel($dbModel);


        $this->injectService($pagesSvc, [
            'model' => $pagesModel,
            'blocksModel' => $blocksModel,
            'mediaModel' => $mediaModel,
            'mediaService' => $mediaSvc,
            'viewModel' => $vm,
        ]);

        $this->injectService($blocksSvc, [
            'model' => $blocksModel,
            'pagesModel' => $pagesModel,
            'mediaModel' => $mediaModel,
            'mediaService' => $mediaSvc,
            'viewModel' => $vm,
        ]);

        $this->injectService($svc, [
            'pagesService' => $pagesSvc,
            'blocksService' => $blocksSvc,
        ]);

        return $svc;
    }

    private function injectService($service, array $deps): void
    {
        $r = new \ReflectionClass($service);
        foreach ($deps as $prop => $val) {
            if ($r->hasProperty($prop)) {
                $p = $r->getProperty($prop);
                $p->setAccessible(true);
                $p->setValue($service, $val);
            }
        }
    }

    public function testBasicViewParamsAndSortHelpers(): void
    {
        $pagesModel = $this->createMock(AdminPagesModel::class);
        $pagesModel->method('getVirtualPages')->willReturn([['sort_order' => 2], ['sort_order' => 5]]);
        $pagesModel->method('getPagesCount')->willReturn(10);
        $pagesModel->method('getPageById')->willReturn(['id' => 1]);

        $blocksModel = $this->createMock(AdminBlocksModel::class);
        $blocksModel->method('getBlocks')->willReturn([['sort_order' => 1], ['sort_order' => 7]]);
        $blocksModel->method('getBlocksCount')->willReturn(20);
        $blocksModel->method('getBlockById')->willReturn(['id' => 2]);

        $vm = $this->createMock(AdminViewModel::class);
        $vm->method('getPagesParams')->willReturnArgument(0);
        $vm->method('getAllPagesParams')->willReturnArgument(0);
        $vm->method('getEditPageParams')->willReturnArgument(0);
        $vm->method('getAllBlocksParams')->willReturnArgument(0);
        $vm->method('getNewBlockParams')->willReturnArgument(0);
        $vm->method('getEditBlockParams')->willReturnArgument(0);

        $svc = $this->makeService(
            pagesModel: $pagesModel,
            blocksModel: $blocksModel,
            vm: $vm
        );

        $this->assertSame(6, $svc->getNextPageSortOrder());
        $this->assertSame(8, $svc->getNextBlockSortOrder());
        $this->assertArrayHasKey('next_sort_order', $svc->getNewPageViewParams());
        $this->assertArrayHasKey('pages', $svc->getAllPagesViewParams());
        $this->assertArrayHasKey('page', $svc->getEditPageViewParams(1));
        $this->assertArrayHasKey('blocks', $svc->getAllBlocksViewParams());
        $this->assertArrayHasKey('next_sort_order', $svc->getNewBlockViewParams());
        $this->assertArrayHasKey('block', $svc->getEditBlockViewParams(2));
    }

    public function testCrudDelegatesToModel(): void
    {
        $pagesModel = $this->createMock(AdminPagesModel::class);
        $pagesModel->expects($this->once())->method('createPage')->with(['title' => 'x', 'virtual_content' => ''])->willReturn(11);
        $pagesModel->expects($this->once())->method('updatePage')->with(11, ['title' => 'y', 'virtual_content' => '']);
        $pagesModel->expects($this->once())->method('deletePage')->with(11);

        $blocksModel = $this->createMock(AdminBlocksModel::class);
        $blocksModel->expects($this->once())->method('createBlock')->with(['title' => 'b', 'content' => ''])->willReturn(22);
        $blocksModel->expects($this->once())->method('updateBlock')->with(22, ['title' => 'c', 'content' => '']);
        $blocksModel->expects($this->once())->method('deleteBlock')->with(22);

        $svc = $this->makeService(pagesModel: $pagesModel, blocksModel: $blocksModel);

        $this->assertSame(11, $svc->createPage(['title' => 'x', 'virtual_content' => '']));
        $svc->updatePage(11, ['title' => 'y', 'virtual_content' => '']);
        $svc->deletePage(11);
        $this->assertSame(22, $svc->createBlock(['title' => 'b', 'content' => '']));
        $svc->updateBlock(22, ['title' => 'c', 'content' => '']);
        $svc->deleteBlock(22);
    }
}
