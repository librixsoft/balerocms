<?php

namespace App\Controllers\Admin;

use App\Services\AdminService;
use Framework\Attributes\Controller;
use Framework\Attributes\Inject;
use Framework\Http\Get;
use Framework\Http\Post;
use Framework\Http\JsonResponse;
use Framework\Http\Auth;
use Framework\Core\View;

#[Controller('/admin')]
#[Auth(required: true)]
class AdminUpdateController
{
    #[Inject]
    private AdminService $adminService;

    #[Inject]
    private View $view;

    #[Get('/update')]
    public function updateSystem()
    {
        $params = $this->adminService->getUpdateViewParams();
        return $this->view->render("admin/dashboard.html", $params, false);
    }

    #[Post('/update/self-update')]
    #[JsonResponse]
    public function selfUpdate()
    {
        return $this->adminService->selfUpdateService();
    }

    #[Post('/update/perform')]
    #[JsonResponse]
    public function performUpdate()
    {
        return $this->adminService->performUpdate();
    }
}
