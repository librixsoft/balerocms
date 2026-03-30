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

    public function image(array $file, array $meta = []): array
    {
        if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
            throw new UploaderException("File upload error. Code: " . ($file['error'] ?? 'unknown'));
        }

        $imageInfo = @getimagesize($file['tmp_name']);
        if (!$imageInfo) {
            throw new UploaderException("The uploaded file is not a valid image.");
        }

        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            throw new UploaderException("Unsupported file extension.");
        }

        $uploadDir = rtrim($this->uploadsPath, '/') . '/';
        if (!is_dir($uploadDir) && !@mkdir($uploadDir, 0755, true)) {
            throw new UploaderException("Failed to create upload directory.");
        }

        $originalName = pathinfo($file['name'], PATHINFO_FILENAME);
        $safeName = preg_replace('/[^a-zA-Z0-9_\-]/', '', $originalName);
        if (empty($safeName)) {
            $safeName = 'image_' . time();
        }
        $filename = $safeName . '.' . $extension;
        $destination = $uploadDir . $filename;

        // If file exists, we might want to append a timestamp to avoid collision since we aren't using hashes
        if (file_exists($destination)) {
            $filename = $safeName . '_' . time() . '.' . $extension;
            $destination = $uploadDir . $filename;
        }

        $url = rtrim($this->configSettings->basepath ?? '/', '/') . '/' . self::RELATIVE_UPLOAD_PATH . $filename;

        if (!$this->moveFile($file['tmp_name'], $destination)) {
            throw new UploaderException("Failed to move uploaded file.");
        }

        $metadata = [
            'name' => $filename,
            'original_name' => (string) ($meta['original_name'] ?? $file['name'] ?? $filename),
            'extension' => $extension,
            'mime' => (string) ($meta['mime'] ?? $imageInfo['mime']),
            'size_bytes' => filesize($destination),
            'width' => $imageInfo[0],
            'height' => $imageInfo[1],
            'url' => $url,
            'uploaded_at' => (string) ($meta['uploaded_at'] ?? date('c')),
            'records' => [],
        ];

        // Save JSON metadata as a fallback or for systems without DB
        if (($this->configSettings->installed ?? 'no') !== 'yes') {
            $jsonPath = $uploadDir . $filename . '.json';
            if (!file_exists($jsonPath)) {
                file_put_contents($jsonPath, json_encode($metadata, JSON_PRETTY_PRINT));
            }
        }

        return $metadata;
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
            $data['records_summary'] = empty($data['records'] ?? []) ? 'Not linked' :
                implode(', ', array_map(fn($r) => ($r['type'] ?? '?') . ' #' . ($r['id'] ?? '?'), $data['records']));

            $bytes = $data['size_bytes'] ?? 0;
            $data['size_formatted'] = ($bytes > 1048576) ? round($bytes / 1048576, 2) . ' MB' : round($bytes / 1024, 2) . ' KB';
            $media[] = $data;
        }

        usort($media, fn($a, $b) => strtotime($b['uploaded_at'] ?? '0') <=> strtotime($a['uploaded_at'] ?? '0'));
        return $media;
    }

    public function addRecordToMetadata(string $name, array $record): void
    {
        $path = rtrim($this->uploadsPath, '/') . '/' . $name . '.json';
        if (!file_exists($path)) {
            return;
        }
        $data = json_decode(file_get_contents($path), true);
        $data['records'] = $data['records'] ?? [];
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

    public function deleteMedia(string $name): void
    {
        $uploadDir = rtrim($this->uploadsPath, '/') . '/';
        $jsonPath = $uploadDir . $name . '.json';
        $filePath = $uploadDir . $name;
        
        if (file_exists($jsonPath)) {
            $data = json_decode(file_get_contents($jsonPath), true);
            if (!empty($data['records'])) {
                throw new UploaderException("Cannot delete media. It is in use (JSON).");
            }
            @unlink($jsonPath);
        }

        if (file_exists($filePath)) {
            @unlink($filePath);
        }
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
