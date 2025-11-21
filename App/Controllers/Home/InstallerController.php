<?php

namespace App\Controllers\Home;

use App\DTO\InstallerDTO;
use App\Services\InstallerService;
use Framework\Attributes\Controller;
use Framework\Attributes\FlashStorage;
use Framework\Attributes\Inject;
use Framework\Core\View;
use Framework\DTO\DTOGenerator;
use Framework\Http\Get;
use Framework\Http\Post;
use Framework\Http\RequestHelper;
use Framework\Utils\Flash;
use Framework\Utils\Redirect;

#[Controller('/installer')]
class InstallerController
{
    #[Inject]
    private View $view;

    #[Inject]
    private InstallerService $installerService;

    #[Inject]
    #[FlashStorage]
    private Flash $flash;

    #[Inject]
    private RequestHelper $requestHelper;

    #[Inject]
    private Redirect $redirect;

    #[Inject]
    private DTOGenerator $dtoGenerator;

    #[Get('/')]
    public function home()
    {
        $params = [];

        if ($this->flash->has('errors')) {
            $params['errors'] = $this->flash->get('errors');
        }

        $params = $this->installerService->prepareInstallerParams($params);

        return $this->view->render("installer/setup_wizard.html", $params, false);
    }

    #[Post('/install')]
    public function postInstall()
    {
        $installerDTO = $this->dtoGenerator->create(InstallerDTO::class);
        $installerDTO->fromRequest($this->requestHelper);

        if ($this->installerService->validateInstaller($installerDTO)) {
            // map and save
            $this->installerService->mapAndSaveSettings($installerDTO);
        } else {
            $this->flash->set('errors', $this->installerService->getValidationErrors());
        }

        $this->redirect->to("/installer/");
    }

    #[Get('progressBar')]
    public function getProgressBar()
    {
        if (!$this->flash->has('install_in_progress')) {
            $this->redirect->to("/installer/");
        }

        $this->installerService->markAsInstalled();
        $params = $this->installerService->prepareProgressBarParams();
        $this->flash->delete('install_in_progress');

        return $this->view->render("installer/progressBar.html", $params, false);
    }

    #[Post('progressBar')]
    public function postProgressBar()
    {
        $this->flash->set('install_in_progress', true);
        $this->installerService->executeInstallation();
        $this->redirect->to("/installer/progressBar");
    }
}