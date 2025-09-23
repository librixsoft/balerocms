<?php

/**
 * Balero CMS
 * @author Anibal Gomez
 * @license GNU General Public License
 */

namespace Modules\Page\Views;

use Framework\Core\ConfigSettings;
use Framework\Core\ViewModel;

class PageViewModel
{
    private ConfigSettings $config;
    private ViewModel $viewModel;

    public function __construct(ConfigSettings $config, ViewModel $viewModel)
    {
        $this->config = $config;
        $this->viewModel = $viewModel;
    }

    /**
     * Pobla todos los parámetros de Page y devuelve el array listo para render.
     *
     * @param array $extraParams Parámetros adicionales (por ejemplo errores o datos específicos).
     */
    public function setPageParams(array $extraParams = []): array
    {
        $this->viewModel->addAll([
            // Etiquetas comunes
            'lbl_virtual_pages' => 'Virtual Pages',
            'lbl_home' => 'Home',
            'lbl_no_pages' => 'No virtual pages available.',

            // Botones
            'btn_refresh' => 'Refresh',
        ]);

        if (!empty($extraParams)) {
            $this->viewModel->addAll($extraParams);
        }

        return $this->viewModel->all();
    }
}
