<?php

/**
 * Balero CMS
 * @author Anibal Gomez <balerocms@gmail.com>
 * @license GNU General Public License
 */

namespace Modules\Page\Controllers;

use Framework\Core\Controller;
use Framework\Http\Get;
use Modules\Page\Models\PageModel;
use Modules\Page\Views\PageViewModel;

class PageController extends Controller
{
    protected PageModel $model;
    protected PageViewModel $viewModel;

    public function __construct(
        PageModel $model,
        PageViewModel $viewModel
    )
    {
        $this->model = $model;
        $this->viewModel = $viewModel;
    }

    #[Get('/')]
    public function home()
    {
        return $this->render("main.html", $this->viewModel->setPageParams([
            'virtual_pages' => $this->model->getVirtualPages(),
        ]));
    }

    #[Get('/{staticUrl}')]
    public function page(string $staticUrl)
    {
        $page = $this->model->getVirtualPageBySlug($staticUrl);

        if (empty($page)) {
            return $this->render("page_detail.html", $this->viewModel->setPageParams([
                'error_message' => "La página solicitada no existe.",
                'virtual_pages' => $this->model->getVirtualPages(),
            ]));
        }

        return $this->render("page_detail.html", $this->viewModel->setPageParams([
            'page' => $page,
            'virtual_pages' => $this->model->getVirtualPages(),
        ]));
    }
}
