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

    /**
     * Procesa la subida de una imagen
     *
     * @param array $file Archivo $_FILES
     * @return array Resultado con status y url/message
     */
    public function uploadImage(array $file): array
    {
        if (empty($file)) {
            return [
                'status' => 'error',
                'message' => 'Input file not found'
            ];
        }

        try {
            $url = $this->uploader->image($file);

            return [
                'status' => 'ok',
                'url' => $url
            ];
        } catch (\Throwable $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }
}