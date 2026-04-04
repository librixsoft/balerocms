<?php

namespace App\Controllers\Admin;

use App\DTO\SettingsDTO;
use App\Services\Admin\AdminSettingsService;
use Framework\Attributes\Controller;
use Framework\Attributes\FlashStorage;
use Framework\Attributes\Inject;
use Framework\Http\Get;
use Framework\Http\Post;
use Framework\Http\Auth;
use Framework\Http\RequestHelper;
use Framework\Core\View;
use Framework\Utils\Flash;
use Framework\Utils\Redirect;

#[Controller('/admin')]
#[Auth(required: true)]
class AdminSettingsController
{
    #[Inject]
    private AdminSettingsService $adminService;

    #[Inject]
    private View $view;

    #[Inject]
    private RequestHelper $request;

    #[Inject]
    #[FlashStorage]
    private Flash $flash;

    #[Inject]
    private Redirect $redirect;

    #[Get('/settings')]
    public function settings()
    {
        $additionalParams = [];

        if ($this->flash->has('errors')) {
            $additionalParams['errors'] = $this->flash->get('errors');
        }

        $params = $this->adminService->getSettingsViewParams($additionalParams);

        return $this->view->render("admin/dashboard.html", $params, false);
    }

    #[Post('/settings')]
    public function postSettings()
    {
        $settingsDTO = new SettingsDTO();
        $settingsDTO->fromRequest($this->request);

        if (!$this->adminService->validateSettings($settingsDTO)) {
            $this->flash->set('errors', $this->adminService->getValidationErrors());
            $this->redirect->to('/admin/settings');
            return;
        }

        $this->adminService->mapAndSaveSettings($settingsDTO);
        $this->redirect->to('/admin/settings');
    }
}
