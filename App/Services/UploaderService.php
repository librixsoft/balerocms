<?php

namespace App\Services;

use Framework\Attributes\Inject;
use Framework\Attributes\Service;
use Framework\IO\Uploader;

#[Service]
class UploaderService
{
    #[Inject]
    private Uploader $uploader;

    public function uploadImage(array $file, array $meta = []): array
    {
        if (empty($file)) {
            throw new \Framework\Exceptions\UploaderException("Input file not found");
        }

        return $this->uploader->image($file, $meta);
    }

    public function unlinkImagesFromRecordJson(int $recordId, string $type): void
    {
        $this->uploader->removeRecordFromAllMetadata($recordId, $type);
    }

    public function linkImageToRecordJson(string $name, array $record): void
    {
        $this->uploader->addRecordToMetadata($name, $record);
    }

    public function getAllMediaJson(): array
    {
        return $this->uploader->getAllMediaMetadata();
    }

    public function deleteMediaFile(string $name): void
    {
        $this->uploader->deleteMedia($name);
    }
}