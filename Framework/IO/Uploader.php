<?php

/**
 * Balero CMS
 * @author Anibal Gomez <balerocms@gmail.com>
 * @license GNU General Public License
 */

namespace Framework\IO;

use Framework\Core\ConfigSettings;
use Framework\Exceptions\UploaderException;

class Uploader
{
    private string $uploadsPath;
    private const RELATIVE_UPLOAD_PATH = "assets/images/uploads/";
    private const ALLOWED_MIME_TYPES = ['image/jpeg', 'image/png', 'image/gif'];
    private const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif'];
    private ConfigSettings $configSettings;

    public function __construct(ConfigSettings $configSettings)
    {
        $this->configSettings = $configSettings;
        $this->uploadsPath = $this->detectUploadsPath();
    }

    private function detectUploadsPath(): string
    {
        $root = $_SERVER['DOCUMENT_ROOT'] ?? dirname($_SERVER['SCRIPT_FILENAME']);
        return rtrim($root, '/') . '/' . self::RELATIVE_UPLOAD_PATH;
    }

    public function image($file, array $meta = []): string
    {
        if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
            throw new UploaderException("File upload error. Code: " . ($file['error'] ?? 'unknown'));
        }

        $imageInfo = getimagesize($file['tmp_name']);
        if ($imageInfo === false) {
            throw new UploaderException("The uploaded file is not a valid image.");
        }

        $mimeType = $imageInfo['mime'];
        if (!in_array($mimeType, self::ALLOWED_MIME_TYPES)) {
            throw new UploaderException("Unsupported image type: $mimeType. Allowed types: JPEG, PNG, GIF.");
        }

        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, self::ALLOWED_EXTENSIONS)) {
            throw new UploaderException("Unsupported file extension: .$extension. Allowed extensions: .jpg, .jpeg, .png, .gif.");
        }

        $uploadDir = rtrim($this->uploadsPath, '/') . '/';

        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
            throw new UploaderException("Failed to create upload directory: $uploadDir");
        }

        if (!is_writable($uploadDir)) {
            throw new UploaderException("Upload directory is not writable: $uploadDir. Set permissions to 755.");
        }

        $hash        = md5_file($file['tmp_name']);
        $filename    = $hash . '.' . $extension;
        $destination = $uploadDir . $filename;
        $url         = rtrim($this->configSettings->basepath, '/') . '/' . self::RELATIVE_UPLOAD_PATH . $filename;

        // Si ya existe la imagen física, comprobamos si hace falta crear el JSON o no, 
        // pero evitamos mover el archivo
        $exists = file_exists($destination);

        if (!$exists) {
            if (!move_uploaded_file($file['tmp_name'], $destination)) {
                throw new UploaderException("Failed to move uploaded file to destination.");
            }
        }

        // Si la imagen existía pero no existía el JSON o es un nuevo archivo, escribimos el JSON
        $jsonPath = $uploadDir . $hash . '.json';
        if (!$exists || !file_exists($jsonPath)) {
            // ── Crear JSON de metadatos junto a la imagen ──────────────────────
            $metadata = [
                'hash'          => $hash,
                'filename'      => $filename,
                'extension'     => $extension,
                'mime'          => $mimeType,
                'size_bytes'    => filesize($destination),
                'width'         => $imageInfo[0],
                'height'        => $imageInfo[1],
                'url'           => $url,
                'uploaded_at'   => date('c'),
                'original_name' => $meta['original_name'] ?? $file['name'],
                'client_size'   => $meta['size']          ?? 0,
                'client_mime'   => $meta['mime']          ?? $mimeType,
                'client_date'   => $meta['uploaded_at']   ?? date('c'),
                'context'       => $meta['context']       ?? 'unknown',
                // Array de registros donde se ha insertado la imagen
                'records'       => [],
            ];

            file_put_contents(
                $jsonPath,
                json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            );
            // ───────────────────────────────────────────────────────────────────
        }

        return $url;
    }

    /**
     * Añade un registro (record) al array de 'records' de una imagen dada.
     * Así soportamos que la misma imagen se use en N lugares distintos.
     *
     * @param string $hash   Nombre del archivo sin extensión (el md5)
     * @param array  $record Datos del registro a agregar (id, type, url)
     */
    public function addRecordToMetadata(string $hash, array $record): void
    {
        $uploadDir = rtrim($this->uploadsPath, '/') . '/';
        $jsonPath  = $uploadDir . $hash . '.json';

        if (!file_exists($jsonPath)) {
            return; // Imagen subida sin JSON, ignorar
        }

        $metadata = json_decode(file_get_contents($jsonPath), true) ?? [];
        if (!isset($metadata['records']) || !is_array($metadata['records'])) {
            $metadata['records'] = [];
        }

        // Verificar si el registro ya está asociado para no duplicarlo
        $exists = false;
        foreach ($metadata['records'] as $r) {
            if (isset($r['id']) && isset($r['type']) && $r['id'] == $record['id'] && $r['type'] === $record['type']) {
                $exists = true;
                break;
            }
        }

        if (!$exists) {
            $metadata['records'][] = $record;
            file_put_contents(
                $jsonPath,
                json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            );
        }
    }
    public function getAllMediaMetadata(): array
    {
        $uploadDir = rtrim($this->uploadsPath, '/') . '/';
        $media = [];

        if (is_dir($uploadDir)) {
            $files = glob($uploadDir . '*.json');
            if ($files) {
                foreach ($files as $file) {
                    $json = file_get_contents($file);
                    if ($json) {
                        $data = json_decode($json, true);
                        if ($data) {
                            // Crear un resumen textual de los registros
                            $recordsStr = '';
                            if (!empty($data['records'])) {
                                $types = [];
                                foreach ($data['records'] as $r) {
                                    $types[] = ($r['type'] ?? '?') . ' #' . ($r['id'] ?? '?');
                                }
                                $recordsStr = implode(', ', $types);
                            } else {
                                $recordsStr = 'Not linked';
                            }
                            $data['records_summary'] = $recordsStr;

                            // Formatear tamaño a KB o MB
                            $bytes = $data['size_bytes'] ?? 0;
                            if ($bytes > 1024 * 1024) {
                                $data['size_formatted'] = round($bytes / (1024 * 1024), 2) . ' MB';
                            } else {
                                $data['size_formatted'] = round($bytes / 1024, 2) . ' KB';
                            }

                            $media[] = $data;
                        }
                    }
                }
            }
        }

        // Ordenar por fecha de subida, más reciente primero
        usort($media, function ($a, $b) {
            $dateA = strtotime($a['uploaded_at'] ?? '0');
            $dateB = strtotime($b['uploaded_at'] ?? '0');
            return $dateB <=> $dateA;
        });

        return $media;
    }

    public function getUploadsPath(): string
    {
        return $this->uploadsPath;
    }

    public function setUploadsPath(string $uploadsPath): void
    {
        $this->uploadsPath = $uploadsPath;
    }
}
