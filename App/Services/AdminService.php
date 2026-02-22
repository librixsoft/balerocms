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

    /**
     * Prepara los parámetros para la vista de configuración
     */
    public function getSettingsViewParams(array $additionalParams = []): array
    {
        $params = [
            'virtual_pages' => $this->model->getVirtualPages(),
            'pages_count' => $this->model->getPagesCount(),
            'blocks_count' => $this->model->getBlocksCount(),
        ];

        return $this->viewModel->getSettingsParams(
            array_merge($params, $additionalParams)
        );
    }

    /**
     * Valida los datos de configuración
     */
    public function validateSettings(SettingsDTO $settingsDTO): bool
    {
        $this->validator->validate($settingsDTO);
        return !$this->validator->fails();
    }

    /**
     * Obtiene los errores de validación
     */
    public function getValidationErrors(): array
    {
        return $this->validator->errors();
    }

    /**
     * Actualiza la configuración del sistema
     */
    public function mapAndSaveSettings(SettingsDTO $settingsDTO): void
    {
        $this->adminSettingsMapper->mapAndSaveSettings($settingsDTO, $this->configSettings);
    }

    /**
     * Prepara los parámetros para la vista de nueva página
     */
    public function getNewPageViewParams(): array
    {
        return $this->viewModel->getPagesParams([
            'pages_count' => $this->model->getPagesCount(),
            'blocks_count' => $this->model->getBlocksCount(),
            'next_sort_order' => $this->getNextPageSortOrder(),
        ]);
    }

    /**
     * Prepara los parámetros para la vista de todas las páginas
     */
    public function getAllPagesViewParams(): array
    {
        return $this->viewModel->getAllPagesParams([
            'pages' => $this->model->getVirtualPages(),
            'pages_count' => $this->model->getPagesCount(),
            'blocks_count' => $this->model->getBlocksCount(),
        ]);
    }

    /**
     * Crea una nueva página
     */
    public function createPage(array $data): int
    {
        return $this->model->createPage($data);
    }

    /**
     * Calcula el próximo orden de clasificación para páginas
     */
    public function getNextPageSortOrder(): int
    {
        $pages = $this->model->getVirtualPages();
        $maxSort = max(array_column($pages, 'sort_order') ?: [0]);
        return $maxSort + 1;
    }

    /**
     * Prepara los parámetros para editar una página
     */
    public function getEditPageViewParams(int $id): array
    {
        return $this->viewModel->getEditPageParams([
            'page' => $this->model->getPageById($id),
            'pages_count' => $this->model->getPagesCount(),
            'blocks_count' => $this->model->getBlocksCount(),
        ]);
    }

    /**
     * Actualiza una página existente
     */
    public function updatePage(int $id, array $data): void
    {
        $this->model->updatePage($id, $data);
    }

    /**
     * Elimina una página
     */
    public function deletePage(int $id): void
    {
        $this->model->deletePage($id);
    }

    /**
     * Prepara los parámetros para la vista de todos los bloques
     */
    public function getAllBlocksViewParams(): array
    {
        return $this->viewModel->getAllBlocksParams([
            'blocks' => $this->model->getBlocks(),
            'pages_count' => $this->model->getPagesCount(),
            'blocks_count' => $this->model->getBlocksCount(),
        ]);
    }

    /**
     * Calcula el próximo orden de clasificación para bloques
     */
    public function getNextBlockSortOrder(): int
    {
        $blocks = $this->model->getBlocks();
        $maxSort = max(array_column($blocks, 'sort_order') ?: [0]);
        return $maxSort + 1;
    }

    /**
     * Prepara los parámetros para crear un nuevo bloque
     */
    public function getNewBlockViewParams(): array
    {
        return $this->viewModel->getNewBlockParams([
            'next_sort_order' => $this->getNextBlockSortOrder(),
            'pages_count' => $this->model->getPagesCount(),
            'blocks_count' => $this->model->getBlocksCount(),
        ]);
    }

    /**
     * Crea un nuevo bloque
     */
    public function createBlock(array $data): int
    {
        return $this->model->createBlock($data);
    }

    /**
     * Prepara los parámetros para editar un bloque
     */
    public function getEditBlockViewParams(int $id): array
    {
        return $this->viewModel->getEditBlockParams([
            'block' => $this->model->getBlockById($id),
            'pages_count' => $this->model->getPagesCount(),
            'blocks_count' => $this->model->getBlocksCount(),
        ]);
    }

    /**
     * Actualiza un bloque existente
     */
    public function updateBlock(int $id, array $data): void
    {
        $this->model->updateBlock($id, $data);
    }

    /**
     * Elimina un bloque
     */
    public function deleteBlock(int $id): void
    {
        $this->model->deleteBlock($id);
    }

    /**
     * Prepara los parámetros para la vista de actualización
     */
    public function getUpdateViewParams(): array
    {
        $updateInfo = $this->updateService->isUpdateAvailable();
        
        // Add pages_count and blocks_count like other views
        $updateInfo['pages_count'] = $this->model->getPagesCount();
        $updateInfo['blocks_count'] = $this->model->getBlocksCount();
        
        return $this->viewModel->getUpdateParams($updateInfo);
    }

    /**
     * Realiza la actualización automática del sistema
     */
    public function performUpdate(): array
    {
        return $this->updateService->performUpdate();
    }

    /**
     * Prepara los parámetros de la galería de medios (Media)
     */
    public function getMediaViewParams(): array
    {
        $params = [
            'pages_count'  => $this->model->getPagesCount(),
            'blocks_count' => $this->model->getBlocksCount(),
            'media_items'  => $this->uploaderService->getAllMedia(),
        ];

        return $this->viewModel->getMediaParams($params);
    }
}