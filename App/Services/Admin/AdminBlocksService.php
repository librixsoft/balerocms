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
class AdminBlocksService
{
    #[Inject]
    private AdminBlocksModel $model;

    #[Inject]
    private AdminPagesModel $pagesModel;

    #[Inject]
    private AdminMediaModel $mediaModel;

    #[Inject]
    private AdminMediaService $mediaService;

    #[Inject]
    private AdminViewModel $viewModel;

    public function getAllBlocksViewParams(): array
    {
        return $this->viewModel->getAllBlocksParams([
            'blocks' => $this->model->getBlocks(),
            'pages_count' => $this->pagesModel->getPagesCount(),
            'blocks_count' => $this->model->getBlocksCount(),
            'media_count' => count($this->mediaModel->getAllMedia()),
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
            'pages_count' => $this->pagesModel->getPagesCount(),
            'blocks_count' => $this->model->getBlocksCount(),
            'media_count' => count($this->mediaModel->getAllMedia()),
        ]);
    }

    public function createBlock(array $data): int
    {
        $id = $this->model->createBlock($data);
        $this->mediaService->linkMediaToRecord($data['content'], $id, 'block', $data['name'] ?? '');
        return $id;
    }

    public function getEditBlockViewParams(int $id): array
    {
        return $this->viewModel->getEditBlockParams([
            'block' => $this->model->getBlockById($id),
            'pages_count' => $this->pagesModel->getPagesCount(),
            'blocks_count' => $this->model->getBlocksCount(),
            'media_count' => count($this->mediaModel->getAllMedia()),
        ]);
    }

    public function updateBlock(int $id, array $data): void
    {
        $this->model->updateBlock($id, $data);
        $this->mediaService->linkMediaToRecord($data['content'], $id, 'block', $data['name'] ?? '');
    }

    public function deleteBlock(int $id): void
    {
        $this->model->deleteBlock($id);
        $this->mediaService->linkMediaToRecord('', $id, 'block', '');
    }
}
