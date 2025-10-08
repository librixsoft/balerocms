<?php

/**
 * Balero CMS
 * @author Anibal Gomez <balerocms@gmail.com>
 * @license GNU General Public License
 */

namespace App\Views;

use Framework\Core\ConfigSettings;
use Framework\Core\ViewModel;

class LoginViewModel
{
    private ConfigSettings $config;
    private ViewModel $viewModel;

    public function __construct(ConfigSettings $config, ViewModel $viewModel)
    {
        $this->config = $config;
        $this->viewModel = $viewModel;
    }

    /**
     * Pobla todos los parámetros de login y devuelve el array listo para render.
     */
    public function setLoginParams(array $extraParams = []): array
    {
        $this->viewModel->addAll([
            // Etiquetas
            'lbl_login' => 'Login',
            'lbl_username' => 'Username',
            'lbl_password' => 'Password',

            // Botones
            'btn_login' => 'Sign In',
        ]);

        if (!empty($extraParams)) {
            $this->viewModel->addAll($extraParams);
        }

        return $this->viewModel->all();
    }
}
