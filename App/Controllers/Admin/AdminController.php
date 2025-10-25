<?php

namespace App\Controllers\Admin;

use Framework\Attributes\Controller;
use Framework\Attributes\FlashStorage;
use Framework\Http\Get;
use Framework\Http\JsonResponse;
use Framework\Http\Post;
use Framework\Http\Auth;
use Framework\Http\RequestHelper;
use Framework\Core\View;
use Framework\Utils\Flash;
use Framework\Utils\Redirect;
use Framework\IO\Uploader;
use App\Models\AdminModel;
use App\Views\AdminViewModel;
use Framework\Utils\Validator;

#[Controller('/admin')]
#[Auth(required: true)]
class AdminController
{
    private AdminModel $model;
    private Uploader $uploader;
    private AdminViewModel $viewModel;
    private Redirect $redirect;
    private View $view;
    private RequestHelper $request;
    private Validator $validator;
    private Flash $flash;

    public function __construct(
        AdminModel $model,
        Uploader $uploader,
        AdminViewModel $viewModel,
        Redirect $redirect,
        View $view,
        RequestHelper $request,
        Validator $validator,
        #[FlashStorage('_admin_flash')]
        Flash $flash,
    )
    {
        $this->model = $model;
        $this->uploader = $uploader;
        $this->viewModel = $viewModel;
        $this->redirect = $redirect;
        $this->view = $view;
        $this->request = $request;
        $this->validator = $validator;
        $this->flash = $flash;
    }

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
        $params = $this->viewModel->getSettingsParams([
            'virtual_pages' => $this->model->getVirtualPages(),
            'pages_count' => $this->model->getPagesCount(),
            'blocks_count' => $this->model->getBlocksCount(),
        ]);

        return $this->view->render("admin/dashboard.html", $params, false);
    }

    #[Post('/settings')]
    public function postSettings()
    {
        $data = [
            'title' => $this->request->post("title"),
            'description' => $this->request->post("description"),
            'keywords' => $this->request->post("keywords"),
            'theme' => $this->request->post("theme"),
            'language' => $this->request->post("language"),
            'footer' => $this->request->post("footer"),
        ];

        $this->validator->input($data)
            ->required('title', 'El título es requerido.');

        if ($this->validator->fails()) {
            $this->flash->set('errors', $this->validator->errors());
            $this->redirect->to('/admin/settings');
            return;
        }

        $this->model->updateSettings($data);
        $this->redirect->to('/admin/settings');
    }

    #[Get('/new-page')]
    public function getPages()
    {
        $params = $this->viewModel->getPagesParams([
            'pages_count' => $this->model->getPagesCount(),
            'blocks_count' => $this->model->getBlocksCount(),
        ]);

        return $this->view->render("admin/dashboard.html", $params, false);
    }

    #[Get('/pages')]
    public function getAllPages()
    {
        $params = $this->viewModel->getAllPagesParams([
            'pages' => $this->model->getVirtualPages(),
            'pages_count' => $this->model->getPagesCount(),
            'blocks_count' => $this->model->getBlocksCount(),
        ]);

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

        $this->model->createPage($data);
        $this->redirect->to('/admin/pages');
    }

    #[Get('/pages/edit/{id}')]
    public function editPage(int $id)
    {
        $params = $this->viewModel->getEditPageParams([
            'page' => $this->model->getPageById($id),
            'pages_count' => $this->model->getPagesCount(),
            'blocks_count' => $this->model->getBlocksCount(),
        ]);

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

        $this->model->updatePage($id, $data);
        $this->redirect->to('/admin/pages');
    }

    #[Post('/pages/delete/{id}')]
    public function postDeletePage(int $id)
    {
        $this->model->deletePage($id);
        $this->redirect->to('/admin/pages');
    }

    #[Post('/uploader')]
    #[JsonResponse]
    public function postUploader()
    {
        if (!isset($_FILES['file'])) {
            return [
                'status' => 'error',
                'message' => 'Input file not found'
            ];
        }

        try {
            $url = $this->uploader->image($_FILES['file']); // retorna URL de la imagen
            return [
                'status' => 'ok',
                'url' => $url
            ];
        } catch (\Throwable $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    #[Get('/blocks')]
    public function listBlocks()
    {
        $params = $this->viewModel->getAllBlocksParams([
            'blocks' => $this->model->getBlocks(),
            'pages_count' => $this->model->getPagesCount(),
            'blocks_count' => $this->model->getBlocksCount(),
        ]);

        return $this->view->render("admin/dashboard.html", $params, false);
    }

    #[Get('/blocks/new')]
    public function newBlock()
    {
        $blocks = $this->model->getBlocks();
        $maxSort = max(array_column($blocks, 'sort_order') ?: [0]);
        $nextSort = $maxSort + 1;

        $params = $this->viewModel->getNewBlockParams([
            'next_sort_order' => $nextSort,
            'pages_count' => $this->model->getPagesCount(),
            'blocks_count' => $this->model->getBlocksCount(),
        ]);

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

        $this->model->createBlock($data);
        $this->redirect->to('/admin/blocks');
    }

    #[Get('/blocks/edit/{id}')]
    public function getEditBlock(int $id)
    {
        $params = $this->viewModel->getEditBlockParams([
            'block' => $this->model->getBlockById($id),
            'pages_count' => $this->model->getPagesCount(),
            'blocks_count' => $this->model->getBlocksCount(),
        ]);

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

        $this->model->updateBlock($id, $data);
        $this->redirect->to('/admin/blocks');
    }

    #[Post('/blocks/delete/{id}')]
    public function deleteBlock(int $id)
    {
        $this->model->deleteBlock($id);
        $this->redirect->to('/admin/blocks');
    }
}
