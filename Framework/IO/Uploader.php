<?php

namespace Framework\IO;

use Framework\Core\ConfigSettings;
use Framework\Exceptions\UploaderException;

class Uploader
{
    private string $uploadsPath;
    private const RELATIVE_UPLOAD_PATH = "assets/images/uploads/";
    private const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif'];
    private ConfigSettings $configSettings;

    public function __construct(ConfigSettings $configSettings, ?string $customPath = null)
    {
        $this->configSettings = $configSettings;
        $this->uploadsPath = $customPath ?? $this->detectUploadsPath();
    }

    private function detectUploadsPath(): string
    {
        $root = $_SERVER['DOCUMENT_ROOT'] ?? dirname($_SERVER['SCRIPT_FILENAME'] ?? '');
        return rtrim($root, '/') . '/' . self::RELATIVE_UPLOAD_PATH;
    }

    protected function moveFile(string $from, string $to): bool
    {
        return (PHP_SAPI === 'cli') ? @copy($from, $to) : @move_uploaded_file($from, $to);
    }

    // Se eliminó el parámetro $meta no utilizado
    public function image(array $file): string
    {
        if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
            throw new UploaderException("File upload error. Code: " . ($file['error'] ?? 'unknown'));
        }

        $imageInfo = @getimagesize($file['tmp_name']);
        if (!$imageInfo) {
            throw new UploaderException("The uploaded file is not a valid image.");
        }

        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, self::ALLOWED_EXTENSIONS)) {
            throw new UploaderException("Unsupported file extension.");
        }

        $uploadDir = rtrim($this->uploadsPath, '/') . '/';
        // Agregadas llaves en el if anidado
        if (!is_dir($uploadDir) && !@mkdir($uploadDir, 0755, true)) {
            throw new UploaderException("Failed to create upload directory.");
        }

        $hash = md5_file($file['tmp_name']);
        $filename = $hash . '.' . $extension;
        $destination = $uploadDir . $filename;
        $url = rtrim($this->configSettings->basepath ?? '/', '/') . '/' . self::RELATIVE_UPLOAD_PATH . $filename;

        // Fusionado el if de existencia con el de movimiento
        if (!file_exists($destination) && !$this->moveFile($file['tmp_name'], $destination)) {
            throw new UploaderException("Failed to move uploaded file.");
        }

        $jsonPath = $uploadDir . $hash . '.json';
        if (!file_exists($jsonPath)) {
            $metadata = [
                'hash' => $hash,
                'filename' => $filename,
                'extension' => $extension,
                'mime' => $imageInfo['mime'],
                'size_bytes' => filesize($destination),
                'width' => $imageInfo[0],
                'height' => $imageInfo[1],
                'url' => $url,
                'uploaded_at' => date('c'),
                'records' => []
            ];
            file_put_contents($jsonPath, json_encode($metadata, JSON_PRETTY_PRINT));
        }

        return $url;
    }

    public function getAllMediaMetadata(): array
    {
        $uploadDir = rtrim($this->uploadsPath, '/') . '/';
        $media = [];
        if (!is_dir($uploadDir)) {
            return [];
        }

        foreach (glob($uploadDir . '*.json') as $file) {
            $data = json_decode(file_get_contents($file), true) ?: [];
            $data['records_summary'] = empty($data['records']) ? 'Not linked' :
                implode(', ', array_map(fn($r) => ($r['type'] ?? '?') . ' #' . ($r['id'] ?? '?'), $data['records']));

            $bytes = $data['size_bytes'] ?? 0;
            $data['size_formatted'] = ($bytes > 1048576) ? round($bytes / 1048576, 2) . ' MB' : round($bytes / 1024, 2) . ' KB';
            $media[] = $data;
        }

        usort($media, fn($a, $b) => strtotime($b['uploaded_at'] ?? '0') <=> strtotime($a['uploaded_at'] ?? '0'));
        return $media;
    }

    public function addRecordToMetadata(string $hash, array $record): void
    {
        $path = rtrim($this->uploadsPath, '/') . '/' . $hash . '.json';
        if (!file_exists($path)) {
            return;
        }
        $data = json_decode(file_get_contents($path), true);
        foreach ($data['records'] as $r) {
            if (($r['id'] ?? null) == $record['id'] && ($r['type'] ?? null) === $record['type']) {
                return;
            }
        }
        $data['records'][] = $record;
        file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT));
    }

    public function removeRecordFromAllMetadata(int $id, string $type): void
    {
        foreach (glob(rtrim($this->uploadsPath, '/') . '/*.json') as $file) {
            $data = json_decode(file_get_contents($file), true);
            $data['records'] = array_values(array_filter($data['records'] ?? [], fn($r) => !($r['id'] == $id && $r['type'] == $type)));
            file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
        }
    }

    public function deleteMedia(string $hash): void
    {
        $path = rtrim($this->uploadsPath, '/') . '/' . $hash . '.json';
        if (!file_exists($path)) {
            throw new UploaderException("Media file metadata not found.");
        }
        $data = json_decode(file_get_contents($path), true);
        if (!empty($data['records'])) {
            throw new UploaderException("Cannot delete media. It is in use.");
        }
        @unlink(rtrim($this->uploadsPath, '/') . '/' . ($data['filename'] ?? ''));
        @unlink($path);
    }

    public function getUploadsPath(): string
    {
        return $this->uploadsPath;
    }

    public function setUploadsPath(string $p): void
    {
        $this->uploadsPath = $p;
    }
}

