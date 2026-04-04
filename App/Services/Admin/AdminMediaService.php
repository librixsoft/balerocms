<?php

namespace App\Services\Admin;

use App\Models\Admin\AdminPagesModel;
use App\Models\Admin\AdminBlocksModel;
use App\Models\Admin\AdminMediaModel;
use App\Services\UploaderService;
use App\Views\AdminViewModel;
use Framework\Attributes\Inject;
use Framework\Attributes\Service;
use Framework\Core\ConfigSettings;

#[Service]
class AdminMediaService
{
    #[Inject]
    private AdminMediaModel $model;

    #[Inject]
    private AdminPagesModel $pagesModel;

    #[Inject]
    private AdminBlocksModel $blocksModel;

    #[Inject]
    private AdminViewModel $viewModel;

    #[Inject]
    private ConfigSettings $configSettings;

    #[Inject]
    private UploaderService $uploaderService;

    public function getAllMedia(): array
    {
        if (($this->configSettings->installed ?? 'no') === 'yes') {
            return $this->model->getAllMedia();
        }
        return $this->uploaderService->getAllMediaJson();
    }

    public function getMediaViewParams(): array
    {
        $mediaItems = $this->getAllMedia();

        $params = [
            'pages_count'  => $this->pagesModel->getPagesCount(),
            'blocks_count' => $this->blocksModel->getBlocksCount(),
            'media_count'  => count($mediaItems),
            'media_items'  => $mediaItems,
        ];

        return $this->viewModel->getMediaParams($params);
    }

    public function saveMediaMetadata(array $metadata): void
    {
        if (($this->configSettings->installed ?? 'no') === 'yes') {
            $existing = $this->model->getMediaByName($metadata['name']);
            if (!$existing) {
                $this->model->insertMedia($metadata);
            }
        }
    }

    public function deleteMedia(string $name): void
    {
        if (($this->configSettings->installed ?? 'no') === 'yes') {
            $media = $this->model->getMediaByName($name);
            if (!$media) {
                throw new \Framework\Exceptions\UploaderException("Media file metadata not found.");
            }
            if (!empty($media['records'])) {
                throw new \Framework\Exceptions\UploaderException("Cannot delete media. It is in use.");
            }
            $this->uploaderService->deleteMediaFile($name);
            $this->model->deleteMedia($name);
        } else {
            $this->uploaderService->deleteMediaFile($name);
        }
    }

    public function linkMediaToRecord(string $htmlContent, int $recordId, string $recordType, string $recordUrl): void
    {
        $this->unlinkOldMedia($recordId, $recordType);

        $names = $this->parseMediaNames($htmlContent);
        foreach ($names as $name) {
            $this->linkSingleMedia($name, $recordId, $recordType, $recordUrl);
        }
    }

    private function unlinkOldMedia(int $recordId, string $recordType): void
    {
        if (($this->configSettings->installed ?? 'no') === 'yes') {
            $this->model->removeRecordFromAllMediaRecords($recordId, $recordType);
        } else {
            $this->uploaderService->unlinkImagesFromRecordJson($recordId, $recordType);
        }
    }

    private function parseMediaNames(string $htmlContent): array
    {
        $pattern = '/assets\/images\/uploads\/([a-z0-9_\-\.]+)/i';
        if (!preg_match_all($pattern, $htmlContent, $matches)) {
            return [];
        }

        return array_unique($matches[1]);
    }

    private function linkSingleMedia(string $name, int $recordId, string $recordType, string $recordUrl): void
    {
        if (($this->configSettings->installed ?? 'no') === 'yes') {
            $this->linkMediaInDb($name, $recordId, $recordType, $recordUrl);
        } else {
            $this->uploaderService->linkImageToRecordJson($name, [
                'id' => $recordId,
                'type' => $recordType,
                'url' => $recordUrl
            ]);
        }
    }

    private function linkMediaInDb(string $name, int $recordId, string $recordType, string $recordUrl): void
    {
        $media = $this->model->getMediaByName($name);
        if (!$media) {
            return;
        }

        $records = $media['records'];
        foreach ($records as $r) {
            if (($r['id'] ?? null) == $recordId && ($r['type'] ?? null) === $recordType) {
                return;
            }
        }

        $records[] = ['id' => $recordId, 'type' => $recordType, 'url' => $recordUrl];
        $this->model->updateMediaRecords($name, $records);
    }
}
