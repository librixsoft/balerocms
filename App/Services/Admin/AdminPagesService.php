<?php

namespace App\Services\Admin;

use App\Models\Admin\AdminPagesModel;
use App\Models\Admin\AdminBlocksModel;
use App\Models\Admin\AdminMediaModel;
use App\Services\Admin\AdminMediaService;
use App\Views\AdminViewModel;
use Framework\Attributes\Inject;
use Framework\Attributes\Service;

#[Service]
class AdminPagesService
{
    #[Inject]
    private AdminPagesModel $model;

    #[Inject]
    private AdminBlocksModel $blocksModel;

    #[Inject]
    private AdminMediaModel $mediaModel;

    #[Inject]
    private AdminMediaService $mediaService;

    #[Inject]
    private AdminViewModel $viewModel;

    public function getNewPageViewParams(): array
    {
        return $this->viewModel->getPagesParams([
            'pages_count' => $this->model->getPagesCount(),
            'blocks_count' => $this->blocksModel->getBlocksCount(),
            'media_count' => count($this->mediaModel->getAllMedia()),
            'next_sort_order' => $this->getNextPageSortOrder(),
        ]);
    }

    public function getAllPagesViewParams(): array
    {
        return $this->viewModel->getAllPagesParams([
            'pages' => $this->model->getVirtualPages(),
            'pages_count' => $this->model->getPagesCount(),
            'blocks_count' => $this->blocksModel->getBlocksCount(),
            'media_count' => count($this->mediaModel->getAllMedia()),
        ]);
    }

    public function createPage(array $data): int
    {
        $id = $this->model->createPage($data);
        $this->mediaService->linkMediaToRecord($data['virtual_content'], $id, 'page', $data['static_url'] ?? '');
        return $id;
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
            'blocks_count' => $this->blocksModel->getBlocksCount(),
            'media_count' => count($this->mediaModel->getAllMedia()),
        ]);
    }

    public function updatePage(int $id, array $data): void
    {
        $this->model->updatePage($id, $data);
        $this->mediaService->linkMediaToRecord($data['virtual_content'], $id, 'page', $data['static_url'] ?? '');
    }

    public function deletePage(int $id): void
    {
        $this->model->deletePage($id);
        $this->mediaService->linkMediaToRecord('', $id, 'page', '');
    }
}
