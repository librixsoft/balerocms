<?php

namespace App\Controllers\Error;

use App\Models\BlockModel;
use App\Models\PageModel;
use App\Views\ErrorViewModel;
use Framework\Attributes\Controller;
use Framework\Attributes\Inject;
use Framework\Core\View;
use Framework\Http\Get;

#[Controller('/error')]
class ErrorController
{

    #[Inject]
    private View $view;

    #[Inject]
    private ErrorViewModel $viewModel;

    #[Inject]
    private BlockModel $blockModel;

    #[Inject]
    private PageModel $pageModel;

    #[Get('/')]
    public function index()
    {
        $params = $this->viewModel->setErrorParams([
            'is_error' => true,
            'blocks' => $this->blockModel->getBlocks(),
            'virtual_pages' => $this->pageModel->getVirtualPages(),
        ]);
        return $this->view->render("main.html", $params);
    }

}
