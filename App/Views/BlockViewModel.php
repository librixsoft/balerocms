<?php

namespace App\Views;

use Framework\Core\ConfigSettings;
use Framework\Core\ViewModel;

class BlockViewModel
{

    private ConfigSettings $config;
    private ViewModel $viewModel;

    public function __construct(ConfigSettings $config)
    {
        $this->config = $config;
        $this->viewModel = new ViewModel();
    }

    /**
     * Prepara los parámetros para renderizar vistas de bloques.
     *
     * @param array $extraParams Parámetros adicionales.
     */
    public function setBlockParams(array $extraParams = []): array
    {
        $this->viewModel->addAll([

            // Etiquetas
            'lbl_blocks' => 'Blocks',
            'lbl_no_blocks' => 'No blocks available.',

            // Botones
            'btn_refresh' => 'Refresh',

        ]);

        if (!empty($extraParams)) {
            $this->viewModel->addAll($extraParams);
        }

        return $this->viewModel->all();
    }
}
