<?php

namespace App\Controllers;

use App\Models\TestModel;
use Framework\Attributes\Inject;
use Framework\Attributes\Controller;
use Framework\Core\View;
use Framework\DI\TestContainer;
use Framework\Http\Get;
use Framework\Http\JsonResponse;

#[Controller('/home')]
class TestController
{

    #[Inject]
    private View $view;

    #[Inject]
    private TestModel $model;

    /**Test without contructor
    private Controller $controller;
    private TestModel $model;
    public function __construct(Controller $controller, TestModel $model)
    {
    $this->controller = $controller;
    $this->model = $model;
    }
     **/

    #[Get('/target')]
    public function getNotification()
    {
        return $this->view->render("test.html", [], useTheme: false);
    }

    #[Get('/debug')]
    #[JsonResponse]
    public function debugMocks()
    {
        // Creamos el contenedor de test
        $container = new TestContainer(fn($class) => new $class());

        // Llamamos debugCreateWithMocks sobre NotificationController
        $debugInfo = $container->debugCreateWithMocks(TestController::class);

        return $debugInfo;
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
