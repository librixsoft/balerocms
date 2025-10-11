<?php

namespace App\Controllers;

use App\DTO\InstallerDTO;
use App\Models\InstallerModel;
use App\Views\InstallerViewModel;
use Framework\Attributes\Controller;
use Framework\Attributes\FlashStorage;
use Framework\Attributes\Inject;
use Framework\Core\View;
use Framework\Http\Get;
use Framework\Http\Post;
use Framework\Http\RequestHelper;
use Framework\Utils\Flash;
use Framework\Utils\Redirect;
use Framework\Utils\Validator;

#[Controller('/installer')]
class InstallerController
{
    #[Inject]
    private View $view;

    #[Inject]
    private InstallerModel $model;

    #[Inject]
    private InstallerViewModel $installerViewModel;

    #[Inject]
    #[FlashStorage('_flash')]
    private Flash $flash;

    #[Inject]
    private RequestHelper $requestHelper;

    #[Inject]
    private Validator $validator;

    #[Inject]
    private Redirect $redirect;

    #[Get('/')]
    public function home()
    {
        $params = [];

        if ($this->flash->has('errors')) {
            $params['errors'] = $this->flash->get('errors');
        }

        $params['db_ok'] = $this->model->canConnectToDatabase();
        $params = $this->installerViewModel->setInstallerParams($params);

        return $this->view->render("installer/setup_wizard.html", $params, false);
    }

    #[Post('/install')]
    public function postInstall()
    {
        $installerDTO = new InstallerDTO();
        $installerDTO->fromRequest($this->requestHelper);

        $this->validator->input((array)$installerDTO)
            ->required('username', 'Username cannot be empty.')
            ->required('passwd', 'Password cannot be empty.')
            ->match('passwd', 'passwd2', 'Passwords do not match.')
            ->email('email', 'Invalid email address.');

        if ($this->validator->fails()) {
            $this->flash->set('errors', $this->validator->errors());
        } else {
            //InstallerMapper::map($installerDTO, $this->configSettings);
        }

        $this->redirect->to("/installer/");
    }

    #[Get('progressBar')]
    public function getProgressBar()
    {
        if (!$this->flash->has('install_in_progress')) {
            $this->redirect->to("/installer/");
        }

        $this->model->setInstalled();
        $params = $this->installerViewModel->setInstallerParams();
        $this->flash->delete('install_in_progress');

        return $this->view->render("installer/progressBar.html", $params, false);
    }

    #[Post('progressBar')]
    public function postProgressBar()
    {
        $this->flash->set('install_in_progress', true);
        $this->model->install();
        $this->redirect->to("/installer/progressBar");
    }
}
