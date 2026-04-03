<?php

namespace App\Controllers\Admin;

use App\Services\AdminService;
use Framework\Attributes\Controller;
use Framework\Attributes\Inject;
use Framework\Attributes\FlashStorage;
use Framework\Http\Get;
use Framework\Http\Post;
use Framework\Http\Auth;
use Framework\Core\View;
use Framework\Utils\Flash;
use Framework\Utils\Redirect;

#[Controller('/admin')]
#[Auth(required: true)]
class AdminThemesController
{
    #[Inject]
    private AdminService $adminService;

    #[Inject]
    private View $view;

    #[Inject]
    #[FlashStorage]
    private Flash $flash;

    #[Inject]
    private Redirect $redirect;

    #[Get('/themes')]
    public function getThemes()
    {
        $params = $this->adminService->getThemesViewParams();
        return $this->view->render("admin/dashboard.html", $params, false);
    }

    #[Post('/themes/upload')]
    public function uploadTheme()
    {
        $file = $_FILES['theme_zip'] ?? null;
        if ($file && $file['error'] === UPLOAD_ERR_OK) {
            try {
                $this->adminService->uploadThemeZip($file);
                $this->flash->set("success", "Theme uploaded successfully.");
            } catch (\Exception $e) {
                $this->flash->set("danger", $e->getMessage());
            }
        } else {
            $this->flash->set("danger", "Failed to upload file.");
        }
        $this->redirect->to('/admin/themes');
    }

    #[Post('/themes/activate/{themeName}')]
    public function activateTheme(string $themeName)
    {
        try {
            $this->adminService->activateTheme($themeName);
            $this->flash->set("success", "Theme '{$themeName}' activated successfully.");
        } catch (\Exception $e) {
            $this->flash->set("danger", "Failed to activate theme.");
        }
        $this->redirect->to('/admin/themes');
    }

    #[Post('/themes/delete/{themeName}')]
    public function deleteTheme(string $themeName)
    {
        try {
            $this->adminService->deleteTheme($themeName);
            $this->flash->set("success", "Theme '{$themeName}' deleted successfully.");
        } catch (\Exception $e) {
            $this->flash->set("danger", $e->getMessage());
        }
        $this->redirect->to('/admin/themes');
    }
}
