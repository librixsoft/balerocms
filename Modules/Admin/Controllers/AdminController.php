<?php

namespace Modules\Admin\Controllers;

use Framework\Core\Controller;
use Framework\Http\Auth;
use Framework\Http\Get;
use Framework\Http\Post;
use Framework\IO\Uploader;
use Framework\Static\Redirect;
use Modules\Admin\Models\AdminModel;
use Modules\Admin\Views\AdminViewModel;

#[Auth(required: true)]
class AdminController extends Controller
{
    protected AdminModel $model;
    private Uploader $uploader;
    private AdminViewModel $viewModel;

    public function __construct(
        AdminModel $model,
        Uploader $uploader,
        AdminViewModel $viewModel
    )
    {
        $this->model = $model;
        $this->uploader = $uploader;
        $this->viewModel = $viewModel;
    }

    #[Get('/')]
    public function home()
    {
        Redirect::to('/admin/settings');
    }

    #[Get('/dashboard')]
    public function dashboard()
    {
        Redirect::to('/admin/settings');
    }

    #[Get('/settings')]
    public function getSettings()
    {
        $params = $this->viewModel->getSettingsParams([
            'virtual_pages' => $this->model->getVirtualPages(),
            'pages_count' => $this->model->getPagesCount(),
            'blocks_count' => $this->model->getBlocksCount(),
        ]);

        return $this->render("admin/dashboard.html", $params, false);
    }

    #[Post('/settings')]
    public function postSettings()
    {
        $data = [
            'title' => $this->requestHelper->post("title"),
            'description' => $this->requestHelper->post("description"),
            'keywords' => $this->requestHelper->post("keywords"),
            'theme' => $this->requestHelper->post("theme"),
            'language' => $this->requestHelper->post("language"),
            'footer' => $this->requestHelper->post("footer"),
        ];

        $this->model->updateSettings($data);

        Redirect::to('/admin/settings');
        return "";
    }

    #[Get('/new-page')]
    public function getPages()
    {
        $params = $this->viewModel->getPagesParams([
            'pages_count' => $this->model->getPagesCount(),
            'blocks_count' => $this->model->getBlocksCount(),
        ]);

        return $this->render("admin/pages/new_page.html", $params, false);
    }

    #[Get('/pages')]
    public function getAllPages()
    {
        $params = $this->viewModel->getAllPagesParams([
            'pages' => $this->model->getVirtualPages(),
            'pages_count' => $this->model->getPagesCount(),
            'blocks_count' => $this->model->getBlocksCount(),
        ]);

        return $this->render("admin/pages/pages.html", $params, false);
    }

    #[Post('/pages/new')]
    public function postNewPage()
    {
        $data = [
            'virtual_title' => $this->requestHelper->post('virtual_title'),
            'static_url' => $this->requestHelper->post('static_url'),
            'virtual_content' => $this->requestHelper->raw('virtual_content'),
            'visible' => (int)$this->requestHelper->post('visible'),
            'date' => $this->requestHelper->post('date'),
        ];

        $this->model->createPage($data);
        Redirect::to('/admin/pages');
    }

    #[Get('/pages/edit/{id}')]
    public function editPage(int $id)
    {
        $params = $this->viewModel->getEditPageParams([
            'page' => $this->model->getPageById($id),
            'pages_count' => $this->model->getPagesCount(),
            'blocks_count' => $this->model->getBlocksCount(),
        ]);

        return $this->render("admin/pages/edit_page.html", $params, false);
    }

    #[Post('/pages/edit/{id}')]
    public function postEditPage(int $id)
    {
        $data = [
            'id' => $id,
            'virtual_title' => $this->requestHelper->post("virtual_title"),
            'static_url' => $this->requestHelper->post("static_url"),
            'virtual_content' => $this->requestHelper->raw("virtual_content"),
        ];

        $this->model->updatePage($id, $data);
        Redirect::to('/admin/pages');
    }

    #[Post('/pages/delete/{id}')]
    public function postDeletePage(int $id)
    {
        $this->model->deletePage($id);
        Redirect::to('/admin/pages');
    }

    #[Post('/uploader')]
    public function postUploader()
    {
        if (!isset($_FILES['file'])) {
            throw new \Exception("input file not exist");
        }

        return $this->uploader->image($_FILES['file']);
    }

    #[Get('/blocks')]
    public function listBlocks()
    {
        $params = $this->viewModel->getAllBlocksParams([
            'blocks' => $this->model->getBlocks(),
            'pages_count' => $this->model->getPagesCount(),
            'blocks_count' => $this->model->getBlocksCount(),
        ]);

        return $this->render("admin/blocks/blocks.html", $params, false);
    }

    #[Get('/blocks/new')]
    public function newBlock()
    {
        $blocks = $this->model->getBlocks();
        $maxSort = 0;
        foreach ($blocks as $b) {
            if ($b['sort_order'] > $maxSort) {
                $maxSort = $b['sort_order'];
            }
        }
        $nextSort = $maxSort + 1;

        $params = $this->viewModel->getNewBlockParams([
            'next_sort_order' => $nextSort,
            'pages_count' => $this->model->getPagesCount(),
            'blocks_count' => $this->model->getBlocksCount(),
        ]);

        return $this->render("admin/blocks/new_block.html", $params, false);
    }

    #[Post('/blocks/new')]
    public function createBlock()
    {
        $data = [
            'name' => $this->requestHelper->post('name'),
            'sort_order' => $this->requestHelper->post('sort_order'),
            'content' => $this->requestHelper->raw('content'),
        ];

        $this->model->createBlock($data);
        Redirect::to('/admin/blocks');
    }

    #[Get('/blocks/edit/{id}')]
    public function getEditBlock(int $id)
    {
        $params = $this->viewModel->getEditBlockParams([
            'block' => $this->model->getBlockById($id),
            'pages_count' => $this->model->getPagesCount(),
            'blocks_count' => $this->model->getBlocksCount(),
        ]);

        return $this->render("admin/blocks/edit_block.html", $params, false);
    }

    #[Post('/blocks/edit/{id}')]
    public function postEditBlock(int $id)
    {
        $data = [
            'name' => $this->requestHelper->post('name'),
            'sort_order' => $this->requestHelper->post('sort_order'),
            'content' => $this->requestHelper->raw('content'),
        ];

        $this->model->updateBlock($id, $data);
        Redirect::to('/admin/blocks');
    }

    #[Post('/blocks/delete/{id}')]
    public function deleteBlock(int $id)
    {
        $this->model->deleteBlock($id);
        Redirect::to('/admin/blocks');
    }
}
