<?php

namespace App\Services\Admin;

use App\DTO\SettingsDTO;
use App\Services\Admin\AdminSettingsService;
use App\Services\Admin\AdminPagesService;
use App\Services\Admin\AdminBlocksService;
use App\Services\Admin\AdminMediaService;
use App\Services\Admin\AdminThemesService;
use App\Services\Admin\AdminUpdateService;
use Framework\Attributes\Inject;
use Framework\Attributes\Service;

/**
 * AdminService acts as a facade for modularized admin services.
 * Logic is moved to specialized classes in App\Services\Admin\.
 */
#[Service]
class AdminService
{
    #[Inject]
    private AdminSettingsService $settingsService;

    #[Inject]
    private AdminPagesService $pagesService;

    #[Inject]
    private AdminBlocksService $blocksService;

    #[Inject]
    private AdminMediaService $mediaService;

    #[Inject]
    private AdminThemesService $themesService;

    #[Inject]
    private AdminUpdateService $updateService;

    // --- Settings ---
    public function getSettingsViewParams(array $additionalParams = []): array
    {
        return $this->settingsService->getSettingsViewParams($additionalParams);
    }

    public function validateSettings(SettingsDTO $settingsDTO): bool
    {
        return $this->settingsService->validateSettings($settingsDTO);
    }

    public function getValidationErrors(): array
    {
        return $this->settingsService->getValidationErrors();
    }

    public function mapAndSaveSettings(SettingsDTO $settingsDTO): void
    {
        $this->settingsService->mapAndSaveSettings($settingsDTO);
    }

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

    // --- Media ---
    public function getAllMedia(): array
    {
        return $this->mediaService->getAllMedia();
    }

    public function getMediaViewParams(): array
    {
        return $this->mediaService->getMediaViewParams();
    }

    public function saveMediaMetadata(array $metadata): void
    {
        $this->mediaService->saveMediaMetadata($metadata);
    }

    public function deleteMedia(string $name): void
    {
        $this->mediaService->deleteMedia($name);
    }

    public function linkMediaToRecord(string $htmlContent, int $recordId, string $recordType, string $recordUrl): void
    {
        $this->mediaService->linkMediaToRecord($htmlContent, $recordId, $recordType, $recordUrl);
    }

    // --- Themes ---
    public function getThemesViewParams(): array
    {
        return $this->themesService->getThemesViewParams();
    }

    public function uploadThemeZip(array $file): void
    {
        $this->themesService->uploadThemeZip($file);
    }

    public function activateTheme(string $themeName): void
    {
        $this->themesService->activateTheme($themeName);
    }

    public function deleteTheme(string $themeName): void
    {
        $this->themesService->deleteTheme($themeName);
    }

    // --- Updates ---
    public function getUpdateViewParams(): array
    {
        return $this->updateService->getUpdateViewParams();
    }

    public function selfUpdateService(): array
    {
        return $this->updateService->selfUpdateService();
    }

    public function performUpdate(): array
    {
        return $this->updateService->performUpdate();
    }
}
