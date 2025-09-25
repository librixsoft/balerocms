<?php

/**
 * Balero CMS
 * @author Anibal Gomez <balerocms@gmail.com>
 * @license GNU General Public License
 */

namespace Framework\IO;

use Exception;
use Framework\Core\ConfigSettings;
use Framework\Core\ErrorConsole;
use Framework\Static\Constant;

class Uploader
{

    private string $uploadsPath = LOCAL_DIR . "/assets/images/uploads/";

    private const ALLOWED_MIME_TYPES = ['image/jpeg', 'image/png', 'image/gif'];
    private const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif'];
    private ConfigSettings $configSettings;

    public function __construct(ConfigSettings $configSettings)
    {
        $this->configSettings = $configSettings;
    }

    public function image($file)
    {
        try {
            if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
                throw new Exception("File upload error. Code: " . $file['error']);
            }

            // Verify it's a valid image
            $imageInfo = getimagesize($file['tmp_name']);
            if ($imageInfo === false) {
                throw new Exception("The uploaded file is not a valid image.");
            }

            $mimeType = $imageInfo['mime'];
            if (!in_array($mimeType, self::ALLOWED_MIME_TYPES)) {
                throw new Exception("Unsupported image type: $mimeType. Allowed types: JPEG, PNG, GIF.");
            }

            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($extension, self::ALLOWED_EXTENSIONS)) {
                throw new Exception("Unsupported file extension: .$extension. Allowed extensions: .jpg, .jpeg, .png, .gif.");
            }

            // Ensure upload directory exists
            $uploadDir = rtrim($this->getUploadsPath(), '/') . '/';
            if (!is_dir($uploadDir)) {
                if (!mkdir($uploadDir, 0777, true)) {
                    throw new Exception("Failed to create upload directory: $uploadDir");
                }
            }

            if (!is_writable($uploadDir)) {
                throw new Exception("Upload directory is not writable: $uploadDir. Set permissions to 777.");
            }

            $filename = md5(uniqid(rand(), true)) . '.' . $extension;
            $destination = $uploadDir . $filename;

            if (!move_uploaded_file($file['tmp_name'], $destination)) {
                throw new Exception("Failed to move uploaded file to destination.");
            }

            return $this->configSettings->basepath .  $this->getUploadsPath() . $filename;

        } catch (Exception $e) {
            ErrorConsole::handleException($e);
            return '';
        }
    }

    /**
     * @return string
     */
    public function getUploadsPath(): string
    {
        return $this->uploadsPath;
    }

    /**
     * @param string $uploadsPath
     */
    public function setUploadsPath(string $uploadsPath): void
    {
        $this->uploadsPath = $uploadsPath;
    }

}
