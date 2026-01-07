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

    public function image($file): string
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

        $filename = md5(uniqid((string)rand(), true)) . '.' . $extension;
        $destination = $uploadDir . $filename;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            throw new UploaderException("Failed to move uploaded file to destination.");
        }

        return rtrim($this->configSettings->basepath, '/') . '/' . self::RELATIVE_UPLOAD_PATH . $filename;
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