<?php

namespace App\Controllers\Admin;

use App\DTO\SettingsDTO;
use App\Services\AdminService;
use App\Services\UploaderService;
use Framework\Attributes\Controller;
use Framework\Attributes\FlashStorage;
use Framework\Attributes\Inject;
use Framework\Http\Get;
use Framework\Http\JsonResponse;
use Framework\Http\Post;
use Framework\Http\Auth;
use Framework\Http\RequestHelper;
use Framework\Core\View;
use Framework\Utils\Flash;
use Framework\Utils\Redirect;

#[Controller('/admin')]
#[Auth(required: true)]
class AdminController
{
    #[Inject]
    private AdminService $adminService;

    #[Inject]
    private UploaderService $uploaderService;

    #[Inject]
    private View $view;

    #[Inject]
    private RequestHelper $request;

    #[Inject]
    #[FlashStorage]
    private Flash $flash;

    #[Inject]
    private Redirect $redirect;

    #[Get('/')]
    public function home()
    {
        $this->redirect->to('/admin/settings');
    }

    #[Get('/dashboard')]
    public function dashboard()
    {
        $this->redirect->to('/admin/settings');
    }

    #[Get('/settings')]
    public function getSettings()
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

        $data = [
            'title' => $settingsDTO->title,
            'description' => $settingsDTO->description,
            'debug' => $settingsDTO->debug,
            'keywords' => $settingsDTO->keywords,
            'theme' => $settingsDTO->theme,
            'language' => $settingsDTO->language,
            'footer' => $settingsDTO->footer,
            'url' => $settingsDTO->url,
        ];

        $this->adminService->updateSettings($data);
        $this->redirect->to('/admin/settings');
    }

    #[Get('/pages/new')]
    public function getPages()
    {
        $params = $this->adminService->getNewPageViewParams();
        return $this->view->render("admin/dashboard.html", $params, false);
    }

    #[Get('/pages')]
    public function getAllPages()
    {
        $params = $this->adminService->getAllPagesViewParams();
        return $this->view->render("admin/dashboard.html", $params, false);
    }

    #[Post('/pages/new')]
    public function postNewPage()
    {
        $data = [
            'virtual_title' => $this->request->post('virtual_title'),
            'static_url' => $this->request->post('static_url'),
            'virtual_content' => $this->request->raw('virtual_content'),
            'visible' => (int)$this->request->post('visible'),
            'date' => $this->request->post('date'),
        ];

        $this->adminService->createPage($data);
        $this->redirect->to('/admin/pages');
    }

    #[Get('/pages/edit/{id}')]
    public function editPage(int $id)
    {
        $params = $this->adminService->getEditPageViewParams($id);
        return $this->view->render("admin/dashboard.html", $params, false);
    }

    #[Post('/pages/edit/{id}')]
    public function postEditPage(int $id)
    {
        $data = [
            'id' => $id,
            'virtual_title' => $this->request->post("virtual_title"),
            'static_url' => $this->request->post("static_url"),
            'virtual_content' => $this->request->raw("virtual_content"),
            'visible' => $this->request->post("visible"),
        ];

        $this->adminService->updatePage($id, $data);
        $this->redirect->to('/admin/pages');
    }

    #[Post('/pages/delete/{id}')]
    public function postDeletePage(int $id)
    {
        $this->adminService->deletePage($id);
        $this->redirect->to('/admin/pages');
    }

    #[Post('/uploader')]
    #[JsonResponse]
    public function postUploader()
    {
        $file = $_FILES['file'] ?? null;
        return $this->uploaderService->uploadImage($file);
    }

    #[Get('/blocks')]
    public function listBlocks()
    {
        $params = $this->adminService->getAllBlocksViewParams();
        return $this->view->render("admin/dashboard.html", $params, false);
    }

    #[Get('/blocks/new')]
    public function newBlock()
    {
        $params = $this->adminService->getNewBlockViewParams();
        return $this->view->render("admin/dashboard.html", $params, false);
    }

    #[Post('/blocks/new')]
    public function createBlock()
    {
        $data = [
            'name' => $this->request->post('name'),
            'sort_order' => $this->request->post('sort_order'),
            'content' => $this->request->raw('content'),
        ];

        $this->adminService->createBlock($data);
        $this->redirect->to('/admin/blocks');
    }

    #[Get('/blocks/edit/{id}')]
    public function getEditBlock(int $id)
    {
        $params = $this->adminService->getEditBlockViewParams($id);
        return $this->view->render("admin/dashboard.html", $params, false);
    }

    #[Post('/blocks/edit/{id}')]
    public function postEditBlock(int $id)
    {
        $data = [
            'name' => $this->request->post('name'),
            'sort_order' => $this->request->post('sort_order'),
            'content' => $this->request->raw('content'),
        ];

        $this->adminService->updateBlock($id, $data);
        $this->redirect->to('/admin/blocks');
    }

    #[Post('/blocks/delete/{id}')]
    public function deleteBlock(int $id)
    {
        $this->adminService->deleteBlock($id);
        $this->redirect->to('/admin/blocks');
    }
}