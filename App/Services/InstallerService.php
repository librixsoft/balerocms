<?php

namespace App\Services;

use App\DTO\InstallerDTO;
use App\Mapper\InstallerMapper;
use App\Models\InstallerModel;
use App\Views\InstallerViewModel;
use Framework\Attributes\Inject;
use Framework\Attributes\Service;
use Framework\Core\ConfigSettings;
use Framework\Utils\Validator;

#[Service]
class InstallerService
{
    #[Inject]
    private InstallerModel $model;

    #[Inject]
    private InstallerViewModel $installerViewModel;

    #[Inject]
    private Validator $validator;

    #[Inject]
    private InstallerMapper $installerMapper;

    #[Inject]
    private ConfigSettings $configSettings;

    /**
     * Verifica si se puede conectar a la base de datos
     */
    public function canConnectToDatabase(): bool
    {
        return $this->model->canConnectToDatabase();
    }

    /**
     * Prepara los parámetros para la vista del instalador
     */
    public function prepareInstallerParams(array $additionalParams = []): array
    {
        $params = $additionalParams;
        $params['db_ok'] = $this->canConnectToDatabase();

        return $this->installerViewModel->setInstallerParams($params);
    }

    /**
     * Valida los datos del instalador
     */
    public function validateInstaller(InstallerDTO $installerDTO): bool
    {
        $this->validator->validate($installerDTO);

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
     * Procesa la instalación con los datos validados
     */
    public function processInstallation(InstallerDTO $installerDTO): void
    {
        $this->installerMapper->map($installerDTO, $this->configSettings);
    }

    /**
     * Ejecuta la instalación completa
     */
    public function executeInstallation(): void
    {
        $this->model->install();
    }

    /**
     * Marca la instalación como completada
     */
    public function markAsInstalled(): void
    {
        $this->model->setInstalled();
    }

    /**
     * Prepara los parámetros para la barra de progreso
     */
    public function prepareProgressBarParams(): array
    {
        return $this->installerViewModel->setInstallerParams();
    }
}