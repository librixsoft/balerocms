<?php

/**
 * Balero CMS
 * @author Anibal Gomez
 * @license GNU General Public License
 */

namespace Modules\Block\Controllers;

use Framework\Core\Controller;
use Framework\Http\Get;
use Modules\Block\Models\BlockModel;
use Modules\Block\Views\BlockViewModel;
use Modules\Page\Models\PageModel;

class BlockController extends Controller
{
    protected BlockViewModel $viewModel;
    protected BlockModel $model;
    protected BlockModel $blockModel;
    protected PageModel $pageModel;

    public function __construct(
        BlockModel $blockModel,
        PageModel $pageModel,
        BlockModel $model,
        BlockViewModel $viewModel
    )
    {
        $this->blockModel = $blockModel;
        $this->pageModel = $pageModel;
        $this->model = $model;
        $this->viewModel = $viewModel;
    }

    #[Get('/')]
    public function index()
    {
        $params = $this->viewModel->setBlockParams([
            'blocks' => $this->blockModel->getBlocks(),
            'virtual_pages' => $this->pageModel->getVirtualPages(),
        ]);
        return $this->render("main.html", $params);
    }

}
