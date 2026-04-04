<?php

namespace App\Services\Admin;

use App\Models\Admin\AdminPagesModel;
use App\Models\Admin\AdminBlocksModel;
use App\Models\Admin\AdminMediaModel;
use App\Services\UpdateService;
use App\Views\AdminViewModel;
use Framework\Attributes\Inject;
use Framework\Attributes\Service;

#[Service]
class AdminUpdateService
{
    #[Inject]
    private AdminPagesModel $pagesModel;

    #[Inject]
    private AdminBlocksModel $blocksModel;

    #[Inject]
    private AdminMediaModel $mediaModel;

    #[Inject]
    private AdminViewModel $viewModel;

    #[Inject]
    private UpdateService $updateService;

    public function getUpdateViewParams(): array
    {
        $updateInfo = $this->updateService->isUpdateAvailable();

        $updateInfo['pages_count'] = $this->pagesModel->getPagesCount();
        $updateInfo['blocks_count'] = $this->blocksModel->getBlocksCount();
        $updateInfo['media_count'] = count($this->mediaModel->getAllMedia());

        return $this->viewModel->getUpdateParams($updateInfo);
    }

    public function selfUpdateService(): array
    {
        return $this->updateService->selfUpdate();
    }

    public function performUpdate(): array
    {
        return $this->updateService->performUpdate();
    }
}
