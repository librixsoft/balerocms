<?php

namespace App\Controllers\Admin;

use App\Services\Admin\AdminPagesService;
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
class AdminPagesController
{
    #[Inject]
    private AdminPagesService $adminService;

    #[Inject]
    private View $view;

    #[Inject]
    private RequestHelper $request;

    #[Inject]
    private Redirect $redirect;

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
            'virtual_title'   => $this->request->post('virtual_title'),
            'static_url'      => $this->request->post('static_url'),
            'virtual_content' => $this->request->raw('virtual_content'),
            'visible'         => (int)$this->request->post('visible'),
            'date'            => $this->request->post('date'),
            'sort_order'      => $this->request->post('sort_order'),
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
            'id'              => $id,
            'virtual_title'   => $this->request->post("virtual_title"),
            'static_url'      => $this->request->post("static_url"),
            'virtual_content' => $this->request->raw("virtual_content"),
            'visible'         => $this->request->post("visible"),
            'sort_order'      => $this->request->post("sort_order"),
        ];

        $this->adminService->updatePage($id, $data);

        $this->redirect->to('/admin/pages');
    }

    #[Post('/pages/delete/{id}')]
    public function deletePage(int $id)
    {
        $this->adminService->deletePage($id);
        $this->redirect->to('/admin/pages');
    }
}
