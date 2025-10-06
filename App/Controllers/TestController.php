<?php

namespace App\Controllers;

use App\Models\TestModel;
use Framework\Attributes\Inject;
use Framework\Attributes\Controller;
use Framework\Core\View;
use Framework\DI\TestContainer;
use Framework\Http\Get;
use Framework\Http\JsonResponse;

#[Controller('/test')]
class TestController
{

    #[Inject]
    private View $view;

    #[Inject]
    private TestModel $model;

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

    #[Get('/model-test')]
    #[JsonResponse]
    public function testModelConnectMethod()
    {
        return $this->model->connect();
    }

}
