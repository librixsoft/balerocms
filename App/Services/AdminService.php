<?php

namespace App\Services;

use App\DTO\SettingsDTO;
use App\Mapper\AdminSettingsMapper;
use App\Models\AdminModel;
use App\Services\UpdateService;
use App\Services\UploaderService;
use App\Views\AdminViewModel;
use Framework\Attributes\Inject;
use Framework\Attributes\Service;
use Framework\Core\ConfigSettings;
use Framework\Utils\Validator;

#[Service]
class AdminService
{
    #[Inject]
    private AdminModel $model;

    #[Inject]
    private AdminViewModel $viewModel;

    #[Inject]
    private Validator $validator;

    #[Inject]
    private AdminSettingsMapper $adminSettingsMapper;

    #[Inject]
    private ConfigSettings $configSettings;

    #[Inject]
    private UpdateService $updateService;

    #[Inject]
    private UploaderService $uploaderService;

    public function getSettingsViewParams(array $additionalParams = []): array
    {
        $params = [
            'virtual_pages' => $this->model->getVirtualPages(),
            'pages_count' => $this->model->getPagesCount(),
            'blocks_count' => $this->model->getBlocksCount(),
        ];

        return $this->viewModel->getSettingsParams(
            array_merge($params, $additionalParams)
        );
    }

    public function validateSettings(SettingsDTO $settingsDTO): bool
    {
        $this->validator->validate($settingsDTO);
        return !$this->validator->fails();
    }

    public function getValidationErrors(): array
    {
        return $this->validator->errors();
    }

    public function mapAndSaveSettings(SettingsDTO $settingsDTO): void
    {
        $this->adminSettingsMapper->mapAndSaveSettings($settingsDTO, $this->configSettings);
    }

    public function getNewPageViewParams(): array
    {
        return $this->viewModel->getPagesParams([
            'pages_count' => $this->model->getPagesCount(),
            'blocks_count' => $this->model->getBlocksCount(),
            'next_sort_order' => $this->getNextPageSortOrder(),
        ]);
    }

    public function getAllPagesViewParams(): array
    {
        return $this->viewModel->getAllPagesParams([
            'pages' => $this->model->getVirtualPages(),
            'pages_count' => $this->model->getPagesCount(),
            'blocks_count' => $this->model->getBlocksCount(),
        ]);
    }

    public function createPage(array $data): int
    {
        return $this->model->createPage($data);
    }

    public function getNextPageSortOrder(): int
    {
        $pages = $this->model->getVirtualPages();
        $maxSort = max(array_column($pages, 'sort_order') ?: [0]);
        return $maxSort + 1;
    }

    public function getEditPageViewParams(int $id): array
    {
        return $this->viewModel->getEditPageParams([
            'page' => $this->model->getPageById($id),
            'pages_count' => $this->model->getPagesCount(),
            'blocks_count' => $this->model->getBlocksCount(),
        ]);
    }

    public function updatePage(int $id, array $data): void
    {
        $this->model->updatePage($id, $data);
    }

    public function deletePage(int $id): void
    {
        $this->model->deletePage($id);
    }

    public function getAllBlocksViewParams(): array
    {
        return $this->viewModel->getAllBlocksParams([
            'blocks' => $this->model->getBlocks(),
            'pages_count' => $this->model->getPagesCount(),
            'blocks_count' => $this->model->getBlocksCount(),
        ]);
    }

    public function getNextBlockSortOrder(): int
    {
        $blocks = $this->model->getBlocks();
        $maxSort = max(array_column($blocks, 'sort_order') ?: [0]);
        return $maxSort + 1;
    }

    public function getNewBlockViewParams(): array
    {
        return $this->viewModel->getNewBlockParams([
            'next_sort_order' => $this->getNextBlockSortOrder(),
            'pages_count' => $this->model->getPagesCount(),
            'blocks_count' => $this->model->getBlocksCount(),
        ]);
    }

    public function createBlock(array $data): int
    {
        return $this->model->createBlock($data);
    }

    public function getEditBlockViewParams(int $id): array
    {
        return $this->viewModel->getEditBlockParams([
            'block' => $this->model->getBlockById($id),
            'pages_count' => $this->model->getPagesCount(),
            'blocks_count' => $this->model->getBlocksCount(),
        ]);
    }

    public function updateBlock(int $id, array $data): void
    {
        $this->model->updateBlock($id, $data);
    }

    public function deleteBlock(int $id): void
    {
        $this->model->deleteBlock($id);
    }

    public function getUpdateViewParams(): array
    {
        $updateInfo = $this->updateService->isUpdateAvailable();

        $updateInfo['pages_count'] = $this->model->getPagesCount();
        $updateInfo['blocks_count'] = $this->model->getBlocksCount();

        return $this->viewModel->getUpdateParams($updateInfo);
    }

    /**
     * Self-update UpdateService.php from repo
     */
    public function selfUpdateService(): array
    {
        return $this->updateService->selfUpdate();
    }

    /**
     * Realiza la actualización automática del sistema
     */
    public function performUpdate(): array
    {
        return $this->updateService->performUpdate();
    }

    public function getMediaViewParams(): array
    {
        $params = [
            'pages_count'  => $this->model->getPagesCount(),
            'blocks_count' => $this->model->getBlocksCount(),
            'media_items'  => $this->uploaderService->getAllMedia(),
        ];

        return $this->viewModel->getMediaParams($params);
    }
}