<?php

namespace App\Services\Admin;

use App\DTO\SettingsDTO;
use App\Mapper\AdminSettingsMapper;
use App\Models\Admin\AdminPagesModel;
use App\Models\Admin\AdminBlocksModel;
use App\Models\Admin\AdminMediaModel;
use App\Views\AdminViewModel;
use Framework\Attributes\Inject;
use Framework\Attributes\Service;
use Framework\Core\ConfigSettings;
use Framework\Utils\Validator;

#[Service]
class AdminSettingsService
{
    #[Inject]
    private AdminPagesModel $pagesModel;

    #[Inject]
    private AdminBlocksModel $blocksModel;
    
    #[Inject]
    private AdminMediaModel $mediaModel;

    #[Inject]
    private AdminViewModel $viewModel;

    #[Inject]
    private Validator $validator;

    #[Inject]
    private AdminSettingsMapper $adminSettingsMapper;

    #[Inject]
    private ConfigSettings $configSettings;

    public function getSettingsViewParams(array $additionalParams = []): array
    {
        $params = [
            'virtual_pages' => $this->pagesModel->getVirtualPages(),
            'pages_count' => $this->pagesModel->getPagesCount(),
            'blocks_count' => $this->blocksModel->getBlocksCount(),
            'media_count' => count($this->mediaModel->getAllMedia()),
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
}
