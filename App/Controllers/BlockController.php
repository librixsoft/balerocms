<?php

namespace App\Controllers;

use App\Models\BlockModel;
use App\Models\PageModel;
use App\Views\BlockViewModel;
use Framework\Attributes\Controller;
use Framework\Attributes\Inject;
use Framework\Core\View;
use Framework\Http\Get;

#[Controller('/')]
class BlockController
{

    #[Inject]
    private View $view;

    #[Inject]
    private BlockViewModel $viewModel;

    #[Inject]
    private BlockModel $model;

    #[Inject]
    private BlockModel $blockModel;

    #[Inject]
    private PageModel $pageModel;

    #[Get('/')]
    public function index()
    {
        $params = $this->viewModel->setBlockParams([
            'blocks' => $this->blockModel->getBlocks(),
            'virtual_pages' => $this->pageModel->getVirtualPages(),
        ]);
        return $this->view->render("main.html", $params);
    }

}
