<?php

namespace App\Controllers\Page;

use App\Models\PageModel;
use App\Views\PageViewModel;
use Framework\Attributes\Controller;
use Framework\Attributes\Inject;
use Framework\Core\View;
use Framework\Http\Get;
use Framework\Http\RequestHelper;
use App\Services\PreviewService;

#[Controller('/page')]
class PageController
{
    #[Inject]
    private View $view;

    #[Inject]
    private PageModel $model;

    #[Inject]
    private PageViewModel $viewModel;

    #[Inject]
    private PreviewService $previewService;

    #[Inject]
    private RequestHelper $requestHelper;

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
            return $this->view->render("main.html", $this->viewModel->setPageParams([
                'error_message' => "La página solicitada no existe.",
                'virtual_pages' => $this->model->getVirtualPages(),
            ]));
        }

        return $this->view->render("main.html", $this->viewModel->setPageParams([
            'page' => $page,
            'virtual_pages' => $this->model->getVirtualPages(),
        ]));
    }

    #[Get('/og/{staticUrl}')]
    public function ogImage(string $staticUrl)
    {
        $page = $staticUrl === 'generic' ? null : $this->model->getVirtualPageBySlug($staticUrl);
        $this->previewService->serveOgImage($page);
    }

}
