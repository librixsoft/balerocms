<?php

namespace App\Models\Admin;

use App\Models\Admin\AdminPagesModel;
use App\Models\Admin\AdminBlocksModel;
use App\Models\Admin\AdminMediaModel;
use Framework\Core\ConfigSettings;
use Framework\Core\Model;
use Framework\Utils\Utils;

/**
 * AdminModel legacy entry point that delegates to modular model classes.
 */
class AdminModel
{
    private ConfigSettings $configSettings;
    private Model $model;
    private Utils $utils;

    private AdminPagesModel $pagesModel;
    private AdminBlocksModel $blocksModel;
    private AdminMediaModel $mediaModel;

    public function __construct(ConfigSettings $configSettings, Model $model, Utils $utils)
    {
        $this->configSettings = $configSettings;
        $this->model = $model;
        $this->utils = $utils;

        // Initialize modular components (manual instantiation because AdminModel is not a framework @Service)
        $this->pagesModel = new AdminPagesModel($model, $utils);
        $this->blocksModel = new AdminBlocksModel($model);
        $this->mediaModel = new AdminMediaModel($model);
    }

    // --- Page Logic ---
    public function getPageById(int $id): ?array
    {
        return $this->pagesModel->getPageById($id);
    }

    public function updatePage(int $id, array $data): bool
    {
        return $this->pagesModel->updatePage($id, $data);
    }

    public function createPage(array $data): int
    {
        return $this->pagesModel->createPage($data);
    }

    public function getPagesCount(): int
    {
        return $this->pagesModel->getPagesCount();
    }

    public function getVirtualPages(): array
    {
        return $this->pagesModel->getVirtualPages();
    }

    public function deletePage(int $id): bool
    {
        return $this->pagesModel->deletePage($id);
    }

    // --- Block Logic ---
    public function getBlocksCount(): int
    {
        return $this->blocksModel->getBlocksCount();
    }

    public function getBlocks(): array
    {
        return $this->blocksModel->getBlocks();
    }

    public function getBlockById(int $id): array
    {
        return $this->blocksModel->getBlockById($id);
    }

    public function createBlock(array $data): int
    {
        return $this->blocksModel->createBlock($data);
    }

    public function updateBlock(int $id, array $data): bool
    {
        return $this->blocksModel->updateBlock($id, $data);
    }

    public function deleteBlock(int $id): bool
    {
        return $this->blocksModel->deleteBlock($id);
    }

    // --- Media Logic ---
    public function getMediaByName(string $name): ?array
    {
        return $this->mediaModel->getMediaByName($name);
    }

    public function insertMedia(array $metadata): void
    {
        $this->mediaModel->insertMedia($metadata);
    }

    public function updateMediaRecords(string $name, array $records): void
    {
        $this->mediaModel->updateMediaRecords($name, $records);
    }

    public function getAllMedia(): array
    {
        return $this->mediaModel->getAllMedia();
    }

    public function deleteMedia(string $name): void
    {
        $this->mediaModel->deleteMedia($name);
    }

    public function removeRecordFromAllMediaRecords(int $id, string $type): void
    {
        $this->mediaModel->removeRecordFromAllMediaRecords($id, $type);
    }
}
