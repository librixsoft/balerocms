<?php

namespace App\Services\Admin;

use Framework\Attributes\Inject;
use Framework\Attributes\Service;

/**
 * AdminContentService acts as a facade for content-related admin services (Pages and Blocks).
 */
#[Service]
class AdminContentService
{
    #[Inject]
    private AdminPagesService $pagesService;

    #[Inject]
    private AdminBlocksService $blocksService;

    // --- Pages ---
    public function getNewPageViewParams(): array
    {
        return $this->pagesService->getNewPageViewParams();
    }

    public function getAllPagesViewParams(): array
    {
        return $this->pagesService->getAllPagesViewParams();
    }

    public function createPage(array $data): int
    {
        return $this->pagesService->createPage($data);
    }

    public function getNextPageSortOrder(): int
    {
        return $this->pagesService->getNextPageSortOrder();
    }

    public function getEditPageViewParams(int $id): array
    {
        return $this->pagesService->getEditPageViewParams($id);
    }

    public function updatePage(int $id, array $data): void
    {
        $this->pagesService->updatePage($id, $data);
    }

    public function deletePage(int $id): void
    {
        $this->pagesService->deletePage($id);
    }

    // --- Blocks ---
    public function getAllBlocksViewParams(): array
    {
        return $this->blocksService->getAllBlocksViewParams();
    }

    public function getNextBlockSortOrder(): int
    {
        return $this->blocksService->getNextBlockSortOrder();
    }

    public function getNewBlockViewParams(): array
    {
        return $this->blocksService->getNewBlockViewParams();
    }

    public function createBlock(array $data): int
    {
        return $this->blocksService->createBlock($data);
    }

    public function getEditBlockViewParams(int $id): array
    {
        return $this->blocksService->getEditBlockViewParams($id);
    }

    public function updateBlock(int $id, array $data): void
    {
        $this->blocksService->updateBlock($id, $data);
    }

    public function deleteBlock(int $id): void
    {
        $this->blocksService->deleteBlock($id);
    }
}
