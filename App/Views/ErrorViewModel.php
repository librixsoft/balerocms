<?php

namespace App\Views;

use Framework\Core\ConfigSettings;
use Framework\Core\ViewModel;

class ErrorViewModel
{

    private ConfigSettings $config;
    private ViewModel $viewModel;

    public function __construct(ConfigSettings $config)
    {
        $this->config = $config;
        $this->viewModel = new ViewModel();
    }

    public function setErrorParams(array $extraParams = []): array
    {
        $this->viewModel->addAll([
            'lbl_error_title' => 'Error',
            'lbl_error_message' => 'An unexpected error has occurred.',
        ]);

        if (!empty($extraParams)) {
            $this->viewModel->addAll($extraParams);
        }

        return $this->viewModel->all();
    }
}
