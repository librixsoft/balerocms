<?php

namespace App\Services;

use App\DTO\SettingsDTO;
use App\Mapper\AdminSettingsMapper;
use App\Models\AdminModel;
use App\Services\UpdateService;
use App\Services\UploaderService;
use App\Views\AdminViewModel;
use Framework\Attributes\Inject;
use Framework\Attributes\Service;
use Framework\Core\ConfigSettings;
use Framework\Utils\Validator;

#[Service]
class AdminService
{
    #[Inject]
    private AdminModel $model;

    #[Inject]
    private AdminViewModel $viewModel;

    #[Inject]
    private Validator $validator;

    #[Inject]
    private AdminSettingsMapper $adminSettingsMapper;

    #[Inject]
    private ConfigSettings $configSettings;

    #[Inject]
    private UpdateService $updateService;

    #[Inject]
    private UploaderService $uploaderService;

    public function getSettingsViewParams(array $additionalParams = []): array
    {
        $params = [
            'virtual_pages' => $this->model->getVirtualPages(),
            'pages_count' => $this->model->getPagesCount(),
            'blocks_count' => $this->model->getBlocksCount(),
            'media_count' => $this->getMediaCount(),
        ];

        return $this->viewModel->getSettingsParams(
            array_merge($params, $additionalParams)
        );
    }

    public function validateSettings(SettingsDTO $settingsDTO): bool
    {
        $this->validator->validate($settingsDTO);
        return !$this->validator->fails();
    }

    public function getValidationErrors(): array
    {
        return $this->validator->errors();
    }

    public function mapAndSaveSettings(SettingsDTO $settingsDTO): void
    {
        $this->adminSettingsMapper->mapAndSaveSettings($settingsDTO, $this->configSettings);
    }

    public function getNewPageViewParams(): array
    {
        return $this->viewModel->getPagesParams([
            'pages_count' => $this->model->getPagesCount(),
            'blocks_count' => $this->model->getBlocksCount(),
            'media_count' => $this->getMediaCount(),
            'next_sort_order' => $this->getNextPageSortOrder(),
        ]);
    }

    public function getAllPagesViewParams(): array
    {
        return $this->viewModel->getAllPagesParams([
            'pages' => $this->model->getVirtualPages(),
            'pages_count' => $this->model->getPagesCount(),
            'blocks_count' => $this->model->getBlocksCount(),
            'media_count' => $this->getMediaCount(),
        ]);
    }

    public function createPage(array $data): int
    {
        $id = $this->model->createPage($data);
        $this->linkMediaToRecord($data['virtual_content'], $id, 'page', $data['static_url'] ?? '');
        return $id;
    }

    public function getNextPageSortOrder(): int
    {
        $pages = $this->model->getVirtualPages();
        $maxSort = max(array_column($pages, 'sort_order') ?: [0]);
        return $maxSort + 1;
    }

    public function getEditPageViewParams(int $id): array
    {
        return $this->viewModel->getEditPageParams([
            'page' => $this->model->getPageById($id),
            'pages_count' => $this->model->getPagesCount(),
            'blocks_count' => $this->model->getBlocksCount(),
            'media_count' => $this->getMediaCount(),
        ]);
    }

    public function updatePage(int $id, array $data): void
    {
        $this->model->updatePage($id, $data);
        $this->linkMediaToRecord($data['virtual_content'], $id, 'page', $data['static_url'] ?? '');
    }

    public function deletePage(int $id): void
    {
        $this->model->deletePage($id);
        $this->linkMediaToRecord('', $id, 'page', '');
    }

    public function getAllBlocksViewParams(): array
    {
        return $this->viewModel->getAllBlocksParams([
            'blocks' => $this->model->getBlocks(),
            'pages_count' => $this->model->getPagesCount(),
            'blocks_count' => $this->model->getBlocksCount(),
            'media_count' => $this->getMediaCount(),
        ]);
    }

    public function getNextBlockSortOrder(): int
    {
        $blocks = $this->model->getBlocks();
        $maxSort = max(array_column($blocks, 'sort_order') ?: [0]);
        return $maxSort + 1;
    }

    public function getNewBlockViewParams(): array
    {
        return $this->viewModel->getNewBlockParams([
            'next_sort_order' => $this->getNextBlockSortOrder(),
            'pages_count' => $this->model->getPagesCount(),
            'blocks_count' => $this->model->getBlocksCount(),
            'media_count' => $this->getMediaCount(),
        ]);
    }

    public function createBlock(array $data): int
    {
        $id = $this->model->createBlock($data);
        $this->linkMediaToRecord($data['content'], $id, 'block', $data['name'] ?? '');
        return $id;
    }

    public function getEditBlockViewParams(int $id): array
    {
        return $this->viewModel->getEditBlockParams([
            'block' => $this->model->getBlockById($id),
            'pages_count' => $this->model->getPagesCount(),
            'blocks_count' => $this->model->getBlocksCount(),
            'media_count' => $this->getMediaCount(),
        ]);
    }

    public function updateBlock(int $id, array $data): void
    {
        $this->model->updateBlock($id, $data);
        $this->linkMediaToRecord($data['content'], $id, 'block', $data['name'] ?? '');
    }

    public function deleteBlock(int $id): void
    {
        $this->model->deleteBlock($id);
        $this->linkMediaToRecord('', $id, 'block', '');
    }

    public function getUpdateViewParams(): array
    {
        $updateInfo = $this->updateService->isUpdateAvailable();

        $updateInfo['pages_count'] = $this->model->getPagesCount();
        $updateInfo['blocks_count'] = $this->model->getBlocksCount();
        $updateInfo['media_count'] = $this->getMediaCount();

        return $this->viewModel->getUpdateParams($updateInfo);
    }

    /**
     * Self-update UpdateService.php from repo
     */
    public function selfUpdateService(): array
    {
        return $this->updateService->selfUpdate();
    }

    /**
     * Realiza la actualización automática del sistema
     */
    public function performUpdate(): array
    {
        return $this->updateService->performUpdate();
    }

    private function getMediaCount(): int
    {
        return count($this->getAllMedia());
    }

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
            'pages_count'  => $this->model->getPagesCount(),
            'blocks_count' => $this->model->getBlocksCount(),
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
        if (($this->configSettings->installed ?? 'no') === 'yes') {
            $this->model->removeRecordFromAllMediaRecords($recordId, $recordType);
        } else {
            $this->uploaderService->unlinkImagesFromRecordJson($recordId, $recordType);
        }

        $pattern = '/assets\/images\/uploads\/([a-zA-Z0-9_\-\.]+)/i';
        if (!preg_match_all($pattern, $htmlContent, $matches)) {
            return;
        }

        $names = array_unique($matches[1]);
        foreach ($names as $name) {
            if (($this->configSettings->installed ?? 'no') === 'yes') {
                $media = $this->model->getMediaByName($name);
                if ($media) {
                    $records = $media['records'];
                    $exists = false;
                    foreach ($records as $r) {
                        if (($r['id'] ?? null) == $recordId && ($r['type'] ?? null) === $recordType) {
                            $exists = true;
                            break;
                        }
                    }
                    if (!$exists) {
                        $records[] = ['id' => $recordId, 'type' => $recordType, 'url' => $recordUrl];
                        $this->model->updateMediaRecords($name, $records);
                    }
                }
            } else {
                $this->uploaderService->linkImageToRecordJson($name, [
                    'id' => $recordId,
                    'type' => $recordType,
                    'url' => $recordUrl
                ]);
            }
        }
    }
    public function getThemesViewParams(): array
    {
        $params = [
            'pages_count'  => $this->model->getPagesCount(),
            'blocks_count' => $this->model->getBlocksCount(),
            'media_count'  => $this->getMediaCount(),
        ];

        return $this->viewModel->getThemesParams($params);
    }

    public function uploadThemeZip(array $file): void
    {
        $zipPath = $file['tmp_name'];
        $zip = new \ZipArchive();
        if ($zip->open($zipPath) !== true) {
            throw new \Exception("Invalid ZIP file.");
        }
        
        $themeName = pathinfo($file['name'], PATHINFO_FILENAME);
        // Ensure valid directory name
        $themeName = preg_replace('/[^a-zA-Z0-9_\-]/', '', $themeName);
        if (empty($themeName)) {
            $zip->close();
            throw new \Exception("Invalid theme name.");
        }

        $publicPath = !empty($_SERVER['DOCUMENT_ROOT']) 
            ? rtrim($_SERVER['DOCUMENT_ROOT'], '/') 
            : rtrim(BASE_PATH, '/') . '/public';

        $publicThemesDir = $publicPath . '/assets/themes/' . $themeName;
        $resourcesThemesDir = rtrim(BASE_PATH, '/') . '/resources/views/themes/' . $themeName;

        // Limpiar el tema anterior si ya existe para reemplazarlo completamente
        if (is_dir($publicThemesDir)) {
            $this->removeDirectory($publicThemesDir);
        }
        if (is_dir($resourcesThemesDir)) {
            $this->removeDirectory($resourcesThemesDir);
        }

        mkdir($publicThemesDir, 0755, true);
        mkdir($resourcesThemesDir, 0755, true);

        // Find the root directory inside the zip (where main.html is located)
        $rootDir = '';
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = str_replace('\\', '/', $zip->getNameIndex($i));
            if (basename($name) === 'main.html') {
                $rootDir = dirname($name);
                if ($rootDir === '.' || $rootDir === '') {
                    $rootDir = '';
                } else {
                    $rootDir .= '/';
                }
                break;
            }
        }

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $filename = str_replace('\\', '/', $zip->getNameIndex($i));
            
            // Skip directories
            if (substr($filename, -1) == '/') {
                continue;
            }

            // Normalizamos removiendo el directorio base del zip si existe
            if ($rootDir !== '' && strpos($filename, $rootDir) === 0) {
                $relativePath = substr($filename, strlen($rootDir));
            } elseif ($rootDir === '') {
                $relativePath = $filename;
            } else {
                continue; // Ignore files outside the theme root
            }

            $content = $zip->getFromIndex($i);
            
            if ($relativePath === 'main.html') {
                file_put_contents($resourcesThemesDir . '/main.html', $content);
            } else {
                $destPath = $publicThemesDir . '/' . $relativePath;
                $destDir = dirname($destPath);
                if (!is_dir($destDir)) mkdir($destDir, 0755, true);
                
                file_put_contents($destPath, $content);
            }
        }
        $zip->close();
    }

    public function activateTheme(string $themeName): void
    {
        // Simple validation to check if the theme directory exists
        $resourcesThemesDir = rtrim(BASE_PATH, '/') . '/resources/views/themes/' . $themeName;
        if (!is_dir($resourcesThemesDir)) {
            throw new \Exception("Theme does not exist.");
        }

        // Setting the theme automatically updates the config JSON mapping.
        $this->configSettings->theme = $themeName;
    }

    public function deleteTheme(string $themeName): void
    {
        $themeName = preg_replace('/[^a-zA-Z0-9_\-]/', '', $themeName);
        if (empty($themeName)) {
            throw new \Exception("Invalid theme name.");
        }

        if ($this->configSettings->theme === $themeName) {
            throw new \Exception("Cannot delete the active theme.");
        }

        $publicPath = !empty($_SERVER['DOCUMENT_ROOT']) 
            ? rtrim($_SERVER['DOCUMENT_ROOT'], '/') 
            : rtrim(BASE_PATH, '/') . '/public';

        $resourcesThemesDir = rtrim(BASE_PATH, '/') . '/resources/views/themes/' . $themeName;
        $publicThemesDir = $publicPath . '/assets/themes/' . $themeName;

        $this->removeDirectory($resourcesThemesDir);
        $this->removeDirectory($publicThemesDir);
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $file) {
            if ($file->isDir()) {
                @rmdir($file->getPathname());
            } else {
                @unlink($file->getPathname());
            }
        }
        @rmdir($dir);
    }
}