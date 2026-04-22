<?php

namespace App\Controllers\Admin;

use App\Services\Admin\AdminBlocksService;
use Framework\Attributes\Controller;
use Framework\Attributes\Inject;
use Framework\Http\Get;
use Framework\Http\Post;
use Framework\Http\Auth;
use Framework\Http\RequestHelper;
use Framework\Core\View;
use Framework\Utils\Redirect;

#[Controller('/admin')]
#[Auth(required: true)]
class AdminBlocksController
{
    private const DASHBOARD_VIEW = "admin/dashboard.html";
    private const BLOCKS_ROUTE = "/admin/blocks";

    #[Inject]
    private AdminBlocksService $adminService;

    #[Inject]
    private View $view;

    #[Inject]
    private RequestHelper $request;

    #[Inject]
    private Redirect $redirect;

    #[Get('/blocks')]
    public function listBlocks()
    {
        $params = $this->adminService->getAllBlocksViewParams();
        return $this->view->render(self::DASHBOARD_VIEW, $params, false);
    }

    #[Get('/blocks/new')]
    public function newBlock()
    {
        $params = $this->adminService->getNewBlockViewParams();
        return $this->view->render(self::DASHBOARD_VIEW, $params, false);
    }

    #[Post('/blocks/new')]
    public function createBlock()
    {
        $data = [
            'name'       => $this->request->post('name'),
            'sort_order' => $this->request->post('sort_order'),
            'content'    => $this->request->raw('content'),
        ];

        $this->adminService->createBlock($data);

        $this->redirect->to(self::BLOCKS_ROUTE);
    }

    #[Get('/blocks/edit/{id}')]
    public function getEditBlock(int $id)
    {
        $params = $this->adminService->getEditBlockViewParams($id);
        return $this->view->render(self::DASHBOARD_VIEW, $params, false);
    }

    #[Post('/blocks/edit/{id}')]
    public function postEditBlock(int $id)
    {
        $data = [
            'name'       => $this->request->post('name'),
            'sort_order' => $this->request->post('sort_order'),
            'content'    => $this->request->raw('content'),
        ];

        $this->adminService->updateBlock($id, $data);

        $this->redirect->to(self::BLOCKS_ROUTE);
    }

    #[Post('/blocks/delete/{id}')]
    public function deleteBlock(int $id)
    {
        $this->adminService->deleteBlock($id);
        $this->redirect->to(self::BLOCKS_ROUTE);
    }
}
