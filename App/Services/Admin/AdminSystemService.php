<?php

namespace App\Services\Admin;

use App\DTO\SettingsDTO;
use Framework\Attributes\Inject;
use Framework\Attributes\Service;

/**
 * AdminSystemService acts as a facade for system-related admin services (Settings, Media, Themes, and Updates).
 */
#[Service]
class AdminSystemService
{
    #[Inject]
    private AdminSettingsService $settingsService;

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
