<?php

namespace App\Controllers;

use App\Models\PageModel;
use App\Views\PageViewModel;
use Framework\Attributes\Controller;
use Framework\Attributes\Inject;
use Framework\Core\View;
use Framework\Http\Get;

#[Controller('/page')]
class PageController
{
    #[Inject]
    private View $view;

    #[Inject]
    private PageModel $model;

    #[Inject]
    private PageViewModel $viewModel;

    #[Get('/')]
    public function home()
    {
        return $this->view->render("main.html", $this->viewModel->setPageParams([
            'virtual_pages' => $this->model->getVirtualPages(),
        ]));
    }

    #[Get('/{staticUrl}')]
    public function page(string $staticUrl)
    {
        $page = $this->model->getVirtualPageBySlug($staticUrl);

        if (empty($page)) {
            return $this->view->render("page_detail.html", $this->viewModel->setPageParams([
                'error_message' => "La página solicitada no existe.",
                'virtual_pages' => $this->model->getVirtualPages(),
            ]));
        }

        return $this->view->render("page_detail.html", $this->viewModel->setPageParams([
            'page' => $page,
            'virtual_pages' => $this->model->getVirtualPages(),
        ]));
    }
}
