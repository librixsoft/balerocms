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
     * Procesa la subida de una imagen y guarda su metadata en JSON.
     *
     * @param array $file  Archivo $_FILES
     * @param array $meta  Metadatos adicionales del archivo
     * @return array Resultado con status y url/message
     */
    public function uploadImage(array $file, array $meta = []): array
    {
        if (empty($file)) {
            return [
                'status'  => 'error',
                'message' => 'Input file not found'
            ];
        }

        try {
            $url = $this->uploader->image($file, $meta);

            return [
                'status' => 'ok',
                'url'    => $url
            ];
        } catch (\Throwable $e) {
            return [
                'status'  => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Tras crear un registro (page/block), parsea el HTML del contenido,
     * encuentra todas las imágenes subidas por el uploader y actualiza
     * sus JSON de metadatos con el ID, tipo y URL virtual del registro.
     *
     * @param string $htmlContent  Contenido HTML del campo (virtual_content / content)
     * @param int    $recordId     ID del registro recién guardado en BD
     * @param string $recordType   'page' | 'block'
     * @param string $recordUrl    URL virtual del registro (ej. /mi-pagina o nombre del bloque)
     */
    public function linkImagesToRecord(string $htmlContent, int $recordId, string $recordType, string $recordUrl): void
    {
        // Patrón de la ruta relativa de uploads: assets/images/uploads/<hash>.<ext>
        $pattern = '/assets\/images\/uploads\/([a-f0-9]{32})\.[a-z]{3,4}/i';

        if (!preg_match_all($pattern, $htmlContent, $matches)) {
            return; // No hay imágenes del uploader en este contenido
        }

        $hashes = array_unique($matches[1]);

        foreach ($hashes as $hash) {
            $this->uploader->addRecordToMetadata($hash, [
                'id'   => $recordId,
                'type' => $recordType,
                'url'  => $recordUrl,
            ]);
        }
    }
}