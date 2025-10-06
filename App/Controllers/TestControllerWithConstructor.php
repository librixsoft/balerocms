<?php

namespace App\Controllers;

use App\Models\TestModel;
use Framework\Attributes\Controller;
use Framework\Core\View;
use Framework\Http\Get;

#[Controller('/test-with-contructor')]
class TestControllerWithConstructor
{

    private View $view;
    private TestModel $model;
    public function __construct(View $view, TestModel $model)
    {
        $this->view = $view;
        $this->model = $model;
    }

    #[Get('/')]
    public function getNotification()
    {
        return $this->view->render("test.html", [], useTheme: false);
    }

    #[Get('/test')]
    public function getNotification1()
    {
        return $this->view->render("test.html", [], useTheme: false);
    }

}
